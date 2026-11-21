<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Enums\Credits\CreditTransactionType;
use App\Enums\Generations\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\Nanodollars;
use App\Services\Pixelforge\PixelforgeClient;
use App\Services\Pixelforge\PixelforgeRequestException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class CostReport
{
    public const int MAXIMUM_DAYS = 92;

    public const int DEFAULT_DAYS = 30;

    private const int PIXELFORGE_USAGE_CACHE_SECONDS = 60;

    public function __construct(private PixelforgeClient $client) {}

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public static function rangeFrom(?string $startDate, ?string $endDate): array
    {
        $today = CarbonImmutable::now('UTC')->startOfDay();
        $end = self::parseDate($endDate) ?? $today;
        $start = self::parseDate($startDate) ?? $end->subDays(self::DEFAULT_DAYS - 1);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        if ($start->diffInDays($end) >= self::MAXIMUM_DAYS) {
            $start = $end->subDays(self::MAXIMUM_DAYS - 1);
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<string, array{date: string, generations: int, pixelforge_generations: int|null, credit_spend: int, provider_cost: int, pixelforge_cost: int|null, difference: int|null, profit: int}>
     */
    public function daily(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $pixelforgeDays = $this->pixelforgeUsageByDate($start, $end);
        $rows = [];

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            $date = $day->toDateString();
            $rows[$date] = [
                'date' => $date,
                'generations' => 0,
                'pixelforge_generations' => $pixelforgeDays[$date]['generations'] ?? null,
                'credit_spend' => 0,
                'provider_cost' => 0,
                'pixelforge_cost' => $pixelforgeDays[$date]['cost'] ?? null,
                'difference' => null,
                'profit' => 0,
            ];
        }

        $this->billedGenerations($start, $end)
            ->select(['id', 'completed_at', 'charged_nanodollars', 'provider_cost_nanodollars'])
            ->lazyById(500)
            ->each(function (Generation $generation) use (&$rows): void {
                $date = $generation->completed_at?->utc()->toDateString();

                if ($date === null || ! isset($rows[$date])) {
                    return;
                }

                $rows[$date]['generations']++;
                $rows[$date]['credit_spend'] += (int) $generation->charged_nanodollars;
                $rows[$date]['provider_cost'] += (int) $generation->provider_cost_nanodollars;
            });

        foreach ($rows as $date => $row) {
            $rows[$date]['profit'] = $row['credit_spend'] - $row['provider_cost'];
            $rows[$date]['difference'] = $row['pixelforge_cost'] === null ? null : $row['provider_cost'] - $row['pixelforge_cost'];
        }

        return array_reverse($rows, true);
    }

    /**
     * @return array<int, array{user_id: int, name: string, email: string, generations: int, credit_spend: int, provider_cost: int, profit: int, credits_bought: int, balance: int}>
     */
    public function perUser(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $spendByUser = $this->billedGenerations($start, $end)
            ->selectRaw('user_id, count(*) as generations, sum(charged_nanodollars) as credit_spend, sum(provider_cost_nanodollars) as provider_cost')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $purchasesByUser = CreditTransaction::query()
            ->where('type', CreditTransactionType::Purchase)
            ->whereBetween('created_at', [$start, $end->endOfDay()])
            ->selectRaw('user_id, sum(amount_nanodollars) as credits_bought')
            ->groupBy('user_id')
            ->pluck('credits_bought', 'user_id');

        $balancesByUser = CreditTransaction::query()
            ->selectRaw('user_id, sum(amount_nanodollars) as balance')
            ->groupBy('user_id')
            ->pluck('balance', 'user_id');

        $userIds = $spendByUser->keys()->merge($purchasesByUser->keys())->unique()->all();

        return User::query()
            ->whereKey($userIds)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(function (User $user) use ($spendByUser, $purchasesByUser, $balancesByUser): array {
                $spend = $spendByUser->get($user->id);
                $creditSpend = (int) ($spend?->getAttribute('credit_spend') ?? 0);
                $providerCost = (int) ($spend?->getAttribute('provider_cost') ?? 0);

                return [$user->id => [
                    'user_id' => $user->id,
                    'name' => trim("{$user->first_name} {$user->last_name}"),
                    'email' => $user->email,
                    'generations' => (int) ($spend?->getAttribute('generations') ?? 0),
                    'credit_spend' => $creditSpend,
                    'provider_cost' => $providerCost,
                    'profit' => $creditSpend - $providerCost,
                    'credits_bought' => (int) ($purchasesByUser->get($user->id) ?? 0),
                    'balance' => (int) ($balancesByUser->get($user->id) ?? 0),
                ]];
            })
            ->all();
    }

    /**
     * @return array{credit_spend: int, provider_cost: int, pixelforge_cost: int|null, profit: int, generations: int}
     */
    public function totals(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $daily = $this->daily($start, $end);
        $pixelforgeCosts = array_column($daily, 'pixelforge_cost');
        $hasCompletePixelforgeUsage = ! in_array(null, $pixelforgeCosts, true);

        return [
            'credit_spend' => array_sum(array_column($daily, 'credit_spend')),
            'provider_cost' => array_sum(array_column($daily, 'provider_cost')),
            'pixelforge_cost' => $hasCompletePixelforgeUsage ? array_sum($pixelforgeCosts) : null,
            'profit' => array_sum(array_column($daily, 'profit')),
            'generations' => array_sum(array_column($daily, 'generations')),
        ];
    }

    /**
     * @return Builder<Generation>
     */
    private function billedGenerations(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return Generation::query()
            ->where('status', GenerationStatus::Succeeded)
            ->whereBetween('completed_at', [$start->startOfDay(), $end->endOfDay()]);
    }

    /**
     * @return array<string, array{generations: int, cost: int}>
     */
    private function pixelforgeUsageByDate(CarbonImmutable $start, CarbonImmutable $end): array
    {
        try {
            return Cache::remember(
                "pixelforge.usage.{$start->toDateString()}.{$end->toDateString()}",
                self::PIXELFORGE_USAGE_CACHE_SECONDS,
                fn (): array => $this->parseUsage($this->client->usage($start, $end)),
            );
        } catch (PixelforgeRequestException|RuntimeException) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $usage
     * @return array<string, array{generations: int, cost: int}>
     */
    private function parseUsage(array $usage): array
    {
        $days = $usage['data'] ?? null;

        if (! is_array($days)) {
            throw new RuntimeException('Pixelforge returned usage without daily data.');
        }

        $usageByDate = [];

        foreach ($days as $day) {
            if (! is_array($day) || ! is_string($day['date'] ?? null) || ! is_string($day['cost_usd'] ?? null)) {
                throw new RuntimeException('Pixelforge returned a malformed usage day.');
            }

            $usageByDate[$day['date']] = [
                'generations' => (int) ($day['generations'] ?? 0),
                'cost' => Nanodollars::fromUsd($day['cost_usd']),
            ];
        }

        return $usageByDate;
    }

    private static function parseDate(?string $date): ?CarbonImmutable
    {
        if ($date === null || ! CarbonImmutable::canBeCreatedFromFormat($date, 'Y-m-d')) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $date, 'UTC')?->startOfDay();
    }
}
