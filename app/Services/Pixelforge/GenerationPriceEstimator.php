<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use RuntimeException;

final class GenerationPriceEstimator
{
    private const int PERCENT = 100;

    public function __construct(private PixelforgePricingService $pricing) {}

    /**
     * @param  array{prompt?: string|null, size: string, quality: string, reference_image_urls?: list<string>|null}  $data
     */
    public function estimate(array $data): GenerationPriceEstimate
    {
        $pricing = $this->pricing->current();
        $tokens = $pricing['tokens'];
        $rates = $pricing['rates'];
        $quality = $data['quality'];
        $size = $data['size'];
        $referenceCount = count($data['reference_image_urls'] ?? []);
        $charactersPerTextToken = (int) $tokens['characters_per_text_token'];
        $textTokenVariancePercent = (int) round(((float) ($tokens['text_token_variance'] ?? 0)) * self::PERCENT);

        $textTokens = intdiv(mb_strlen((string) ($data['prompt'] ?? '')) + $charactersPerTextToken - 1, $charactersPerTextToken);
        $minimumTextTokens = intdiv($textTokens * (self::PERCENT - $textTokenVariancePercent), self::PERCENT);
        $maximumTextTokens = intdiv($textTokens * (self::PERCENT + $textTokenVariancePercent) + self::PERCENT - 1, self::PERCENT);
        $tokensPerReferenceImage = (int) $tokens['per_reference_image'];
        $imageTokens = $referenceCount * $tokensPerReferenceImage;
        $outputTokens = (int) ($tokens['output'][$quality][$size] ?? 0);
        $reasoning = $tokens['reasoning'][$quality] ?? null;

        if ($outputTokens < 1 || ! is_array($reasoning) || ! isset($reasoning['min'], $reasoning['max'])) {
            throw new RuntimeException('Pixelforge token pricing is incomplete.');
        }

        $minimumReasoningTokens = (int) $reasoning['min'];
        $maximumReasoningTokens = (int) $reasoning['max'];
        $typicalReasoningTokens = intdiv($minimumReasoningTokens + $maximumReasoningTokens, 2);

        $providerCost = Nanodollars::providerCost($rates, $textTokens, $imageTokens, $outputTokens, $typicalReasoningTokens);

        return new GenerationPriceEstimate(
            providerCost: $providerCost,
            userCost: Nanodollars::withMarkup($providerCost),
            minimumUserCost: Nanodollars::withMarkup(Nanodollars::providerCost($rates, $minimumTextTokens, $imageTokens, $outputTokens, $minimumReasoningTokens)),
            maximumUserCost: Nanodollars::withMarkup(Nanodollars::providerCost($rates, $maximumTextTokens, $imageTokens, $outputTokens, $maximumReasoningTokens)),
            perReferenceImageUserCost: Nanodollars::withMarkup(Nanodollars::providerCost($rates, 0, $tokensPerReferenceImage, 0, 0)),
            textTokens: $textTokens,
            minimumTextTokens: $minimumTextTokens,
            maximumTextTokens: $maximumTextTokens,
            imageTokens: $imageTokens,
            outputTokens: $outputTokens,
            minimumReasoningTokens: $minimumReasoningTokens,
            maximumReasoningTokens: $maximumReasoningTokens,
        );
    }
}
