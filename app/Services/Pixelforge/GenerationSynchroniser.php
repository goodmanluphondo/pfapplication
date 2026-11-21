<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use App\Enums\Generations\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class GenerationSynchroniser
{
    private const int SECONDS_BETWEEN_POLLS = 5;

    public function __construct(private PixelforgeClient $client) {}

    /**
     * @param  array<string, mixed>  $pixelforgeGeneration
     */
    public function apply(Generation $generation, array $pixelforgeGeneration): void
    {
        DB::transaction(function () use ($generation, $pixelforgeGeneration): void {
            $generation = Generation::query()->whereKey($generation->id)->lockForUpdate()->firstOrFail();
            $incomingStatus = GenerationStatus::tryFrom((string) ($pixelforgeGeneration['status'] ?? ''));

            $generation->pixelforge_id ??= (string) $pixelforgeGeneration['id'];
            $generation->last_synced_at = now();

            if ($incomingStatus !== null && $generation->status->canMoveTo($incomingStatus)) {
                $generation->fill($this->attributesFor($incomingStatus, $pixelforgeGeneration));
            }

            $generation->save();
        });

        $generation->refresh();
    }

    public function refreshPendingFor(User $user): void
    {
        $user->generations()
            ->pending()
            ->whereNotNull('pixelforge_id')
            ->where(fn ($query) => $query
                ->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<=', now()->subSeconds(self::SECONDS_BETWEEN_POLLS)))
            ->get()
            ->each(function (Generation $generation): void {
                try {
                    $this->apply($generation, $this->client->retrieveGeneration((string) $generation->pixelforge_id));
                } catch (PixelforgeRequestException $exception) {
                    Log::warning('Could not refresh a Pixelforge generation.', [
                        'generation_id' => $generation->id,
                        'error_code' => $exception->errorCode,
                    ]);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $pixelforgeGeneration
     * @return array<string, mixed>
     */
    private function attributesFor(GenerationStatus $status, array $pixelforgeGeneration): array
    {
        $attributes = [
            'status' => $status,
            'started_at' => $this->timestamp($pixelforgeGeneration['started_at'] ?? null),
            'completed_at' => $this->timestamp($pixelforgeGeneration['completed_at'] ?? null),
        ];

        if ($status === GenerationStatus::Succeeded) {
            $attributes = [...$attributes, ...$this->billingAttributes($pixelforgeGeneration)];
        }

        if ($status === GenerationStatus::Failed) {
            $attributes['error_code'] = $pixelforgeGeneration['error']['code'] ?? null;
            $attributes['error_message'] = $pixelforgeGeneration['error']['message'] ?? null;
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $pixelforgeGeneration
     * @return array<string, mixed>
     */
    private function billingAttributes(array $pixelforgeGeneration): array
    {
        $usage = $pixelforgeGeneration['usage'];
        $rates = $pixelforgeGeneration['rates'];
        $providerCost = Nanodollars::providerCost(
            $rates,
            (int) $usage['input_text_tokens'],
            (int) $usage['input_image_tokens'],
            (int) $usage['output_tokens'],
            (int) $usage['reasoning_tokens'],
        );

        return [
            'input_text_tokens' => (int) $usage['input_text_tokens'],
            'input_image_tokens' => (int) $usage['input_image_tokens'],
            'output_tokens' => (int) $usage['output_tokens'],
            'reasoning_tokens' => (int) $usage['reasoning_tokens'],
            'rates' => $rates,
            'provider_cost_nanodollars' => $providerCost,
            'charged_nanodollars' => Nanodollars::withMarkup($providerCost),
            'image_url' => $pixelforgeGeneration['image_url'] ?? null,
            'image_url_expires_at' => $this->timestamp($pixelforgeGeneration['image_url_expires_at'] ?? null),
        ];
    }

    private function timestamp(mixed $value): ?Carbon
    {
        return is_string($value) ? Carbon::parse($value) : null;
    }
}
