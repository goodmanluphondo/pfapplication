<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PixelforgePricingService
{
    /** @return array<string, mixed> */
    public function current(): array
    {
        return Cache::remember('pixelforge.pricing', now()->addMinutes(15), function (): array {
            $pricing = Http::connectTimeout(3)
                ->timeout(5)
                ->get(rtrim((string) config('services.pixelforge.base_url'), '/').'/pricing')
                ->throw()
                ->json();

            if (! is_array($pricing)
                || ($pricing['object'] ?? null) !== 'pricing'
                || ! is_array($pricing['rates'] ?? null)
                || ! is_array($pricing['tokens'] ?? null)) {
                throw new RuntimeException('Pixelforge returned an invalid pricing response.');
            }

            foreach (['input_text_tokens', 'input_image_tokens', 'output_tokens', 'reasoning_tokens'] as $rate) {
                if (! isset($pricing['rates'][$rate]) || ! is_string($pricing['rates'][$rate])) {
                    throw new RuntimeException('Pixelforge pricing is missing a token rate.');
                }
            }

            return $pricing;
        });
    }
}
