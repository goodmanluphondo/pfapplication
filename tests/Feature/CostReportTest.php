<?php

use App\Enums\Users\UserRole;
use App\Filament\Pages\CostReport as CostReportPage;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Reporting\CostReport;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

const USAGE_URL = 'pixelforge.promptedit.dev/v1/usage*';

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
    $this->day = CarbonImmutable::parse('2026-11-10', 'UTC');
});

function usageResponse(string $date, string $costUsd, int $generations = 2): array
{
    return [
        'object' => 'usage',
        'start_date' => $date,
        'end_date' => $date,
        'data' => [['date' => $date, 'generations' => $generations, 'cost_usd' => $costUsd]],
        'total' => ['generations' => $generations, 'cost_usd' => $costUsd],
    ];
}

function billedGeneration(User $user, CarbonImmutable $completedAt, int $providerCost, int $charged): Generation
{
    return Generation::factory()->for($user)->succeeded()->create([
        'provider_cost_nanodollars' => $providerCost,
        'charged_nanodollars' => $charged,
        'completed_at' => $completedAt,
    ]);
}

test('daily rows add up spend, provider cost and profit for the day each image finished', function () {
    $user = User::factory()->create();
    billedGeneration($user, $this->day->setTime(23, 59), 40_000_000, 50_000_000);
    billedGeneration($user, $this->day->setTime(1, 0), 60_000_000, 75_000_000);
    billedGeneration($user, $this->day->addDay()->setTime(0, 1), 10_000_000, 12_500_000);
    Generation::factory()->for($user)->failed()->create(['completed_at' => $this->day]);
    Http::fake([USAGE_URL => Http::response(usageResponse('2026-11-10', '0.100000000'))]);

    $rows = app(CostReport::class)->daily($this->day, $this->day);

    expect($rows['2026-11-10'])->toMatchArray([
        'generations' => 2,
        'credit_spend' => 125_000_000,
        'provider_cost' => 100_000_000,
        'profit' => 25_000_000,
        'pixelforge_cost' => 100_000_000,
        'difference' => 0,
    ]);
});

test('a difference from the Pixelforge usage report is surfaced', function () {
    $user = User::factory()->create();
    billedGeneration($user, $this->day->setTime(12, 0), 40_000_000, 50_000_000);
    Http::fake([USAGE_URL => Http::response(usageResponse('2026-11-10', '0.045000000', 1))]);

    $rows = app(CostReport::class)->daily($this->day, $this->day);

    expect($rows['2026-11-10']['difference'])->toBe(-5_000_000);
});

test('the report still works when Pixelforge usage is unavailable', function () {
    $user = User::factory()->create();
    billedGeneration($user, $this->day->setTime(12, 0), 40_000_000, 50_000_000);
    Http::fake([USAGE_URL => Http::response([], 503)]);

    $rows = app(CostReport::class)->daily($this->day, $this->day);

    expect($rows['2026-11-10']['pixelforge_cost'])->toBeNull()
        ->and($rows['2026-11-10']['difference'])->toBeNull()
        ->and($rows['2026-11-10']['credit_spend'])->toBe(50_000_000);
});

test('per-user rows show spend, profit, credits bought and the current balance', function () {
    $user = User::factory()->create();
    $quietUser = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchaseOfDollars(5)->create(['created_at' => $this->day->setTime(9, 0)]);
    billedGeneration($user, $this->day->setTime(12, 0), 40_000_000, 50_000_000);
    Http::fake([USAGE_URL => Http::response(usageResponse('2026-11-10', '0.040000000', 1))]);

    $rows = app(CostReport::class)->perUser($this->day, $this->day);

    expect($rows)->toHaveCount(1)->and($rows[$user->id])->toMatchArray([
        'generations' => 1,
        'credit_spend' => 50_000_000,
        'provider_cost' => 40_000_000,
        'profit' => 10_000_000,
        'credits_bought' => 5_000_000_000,
        'balance' => 5_000_000_000,
    ])->and($rows)->not->toHaveKey($quietUser->id);
});

test('the date range is limited to the 92 days Pixelforge reports on', function () {
    ['start' => $start, 'end' => $end] = CostReport::rangeFrom('2025-01-01', '2026-11-10');

    expect($end->toDateString())->toBe('2026-11-10')
        ->and((int) $start->diffInDays($end))->toBe(CostReport::MAXIMUM_DAYS - 1);
});

test('only system administrators can open the report', function () {
    Filament::setCurrentPanel('admin');
    $admin = User::factory()->create(['role' => UserRole::SystemAdmin]);
    Http::fake([USAGE_URL => Http::response(usageResponse(now('UTC')->toDateString(), '0.000000000', 0))]);

    $this->actingAs(User::factory()->create(), 'admin')->get(CostReportPage::getUrl())->assertForbidden();
    $this->actingAs($admin, 'admin')->get(CostReportPage::getUrl())->assertOk()->assertSee('Cost report');
});
