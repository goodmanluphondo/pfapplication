<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

final readonly class GenerationPriceEstimate
{
    public function __construct(
        public int $providerCost,
        public int $userCost,
        public int $minimumUserCost,
        public int $maximumUserCost,
        public int $perReferenceImageUserCost,
        public int $textTokens,
        public int $minimumTextTokens,
        public int $maximumTextTokens,
        public int $imageTokens,
        public int $outputTokens,
        public int $minimumReasoningTokens,
        public int $maximumReasoningTokens,
    ) {}

    /**
     * @return array{
     *     estimated_provider_cost_usd: string,
     *     estimated_user_cost_usd: string,
     *     minimum_user_cost_usd: string,
     *     maximum_user_cost_usd: string,
     *     per_reference_image_user_cost_usd: string,
     *     tokens: array{
     *         input_text: array{estimated: int, minimum: int, maximum: int},
     *         input_image: int,
     *         output: int,
     *         reasoning: array{minimum: int, maximum: int},
     *     },
     * }
     */
    public function toArray(): array
    {
        return [
            'estimated_provider_cost_usd' => Nanodollars::toUsd($this->providerCost),
            'estimated_user_cost_usd' => Nanodollars::toUsd($this->userCost),
            'minimum_user_cost_usd' => Nanodollars::toUsd($this->minimumUserCost),
            'maximum_user_cost_usd' => Nanodollars::toUsd($this->maximumUserCost),
            'per_reference_image_user_cost_usd' => Nanodollars::toUsd($this->perReferenceImageUserCost),
            'tokens' => [
                'input_text' => [
                    'estimated' => $this->textTokens,
                    'minimum' => $this->minimumTextTokens,
                    'maximum' => $this->maximumTextTokens,
                ],
                'input_image' => $this->imageTokens,
                'output' => $this->outputTokens,
                'reasoning' => [
                    'minimum' => $this->minimumReasoningTokens,
                    'maximum' => $this->maximumReasoningTokens,
                ],
            ],
        ];
    }
}
