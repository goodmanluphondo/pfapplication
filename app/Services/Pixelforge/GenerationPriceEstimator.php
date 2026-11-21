<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use RuntimeException;

class GenerationPriceEstimator
{
    /** @return array{estimated_provider_cost_usd: string, estimated_user_cost_usd: string} */
    public function estimate(array $data): array
    {
        $pricing = app(PixelforgePricingService::class)->current();
        $tokens = $pricing['tokens'];
        $quality = $data['quality'];
        $size = $data['size'];
        $referenceCount = count($data['reference_image_urls'] ?? []);

        $textTokens = intdiv(mb_strlen($data['prompt']) + (int) $tokens['characters_per_text_token'] - 1, (int) $tokens['characters_per_text_token']);
        $imageTokens = $referenceCount * (int) $tokens['per_reference_image'];
        $outputTokens = (int) ($tokens['output'][$quality][$size] ?? 0);
        $reasoning = $tokens['reasoning'][$quality] ?? null;

        if ($outputTokens < 1 || ! is_array($reasoning) || ! isset($reasoning['min'], $reasoning['max'])) {
            throw new RuntimeException('Pixelforge token pricing is incomplete.');
        }

        $reasoningTokens = intdiv((int) $reasoning['min'] + (int) $reasoning['max'], 2);
        $providerCost = ($textTokens * $this->nanoDollarsPerToken($pricing['rates']['input_text_tokens']))
            + ($imageTokens * $this->nanoDollarsPerToken($pricing['rates']['input_image_tokens']))
            + ($outputTokens * $this->nanoDollarsPerToken($pricing['rates']['output_tokens']))
            + ($reasoningTokens * $this->nanoDollarsPerToken($pricing['rates']['reasoning_tokens']));

        return [
            'estimated_provider_cost_usd' => $this->formatUsd($providerCost),
            'estimated_user_cost_usd' => $this->formatUsd(intdiv($providerCost * 5 + 3, 4)),
        ];
    }

    private function nanoDollarsPerToken(string $ratePerMillion): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $ratePerMillion)) {
            throw new RuntimeException('Pixelforge returned a token rate with unsupported precision.');
        }

        [$whole, $fraction] = array_pad(explode('.', $ratePerMillion, 2), 2, '');
        $rateThousandths = ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');

        return $rateThousandths;
    }

    private function formatUsd(int $nanoDollars): string
    {
        return sprintf('%d.%09d', intdiv($nanoDollars, 1_000_000_000), $nanoDollars % 1_000_000_000);
    }
}
