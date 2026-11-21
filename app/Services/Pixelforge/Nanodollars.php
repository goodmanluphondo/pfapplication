<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use RuntimeException;

final class Nanodollars
{
    private const int PER_DOLLAR = 1_000_000_000;

    private const int MARKUP_NUMERATOR = 5;

    private const int MARKUP_DENOMINATOR = 4;

    public static function fromRatePerMillionTokens(string $ratePerMillion): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $ratePerMillion)) {
            throw new RuntimeException('Pixelforge returned a token rate with unsupported precision.');
        }

        [$whole, $fraction] = array_pad(explode('.', $ratePerMillion, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad($fraction, 3, '0');
    }

    /**
     * @param  array{input_text_tokens: string, input_image_tokens: string, output_tokens: string, reasoning_tokens: string}  $rates
     */
    public static function providerCost(array $rates, int $textTokens, int $imageTokens, int $outputTokens, int $reasoningTokens): int
    {
        return ($textTokens * self::fromRatePerMillionTokens($rates['input_text_tokens']))
            + ($imageTokens * self::fromRatePerMillionTokens($rates['input_image_tokens']))
            + ($outputTokens * self::fromRatePerMillionTokens($rates['output_tokens']))
            + ($reasoningTokens * self::fromRatePerMillionTokens($rates['reasoning_tokens']));
    }

    public static function withMarkup(int $providerCost): int
    {
        return intdiv($providerCost * self::MARKUP_NUMERATOR + self::MARKUP_DENOMINATOR - 1, self::MARKUP_DENOMINATOR);
    }

    public static function toUsd(int $nanodollars): string
    {
        return sprintf('%d.%09d', intdiv($nanodollars, self::PER_DOLLAR), $nanodollars % self::PER_DOLLAR);
    }
}
