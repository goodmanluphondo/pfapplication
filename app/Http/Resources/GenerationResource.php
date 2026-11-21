<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Generation;
use App\Services\Pixelforge\Nanodollars;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Generation
 */
final class GenerationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasImageExpired = $this->hasImageExpired();

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'prompt' => $this->prompt,
            'size' => $this->size,
            'quality' => $this->quality,
            'reference_image_count' => count($this->reference_image_urls),
            'estimated_cost_usd' => Nanodollars::toUsd($this->estimated_cost_nanodollars),
            'estimated_minimum_cost_usd' => Nanodollars::toUsd($this->estimated_minimum_cost_nanodollars),
            'estimated_maximum_cost_usd' => Nanodollars::toUsd($this->estimated_maximum_cost_nanodollars),
            'charged_usd' => $this->charged_nanodollars === null ? null : Nanodollars::toUsd($this->charged_nanodollars),
            'estimated_tokens' => $this->estimated_tokens,
            'usage' => $this->input_text_tokens === null ? null : [
                'input_text_tokens' => $this->input_text_tokens,
                'input_image_tokens' => $this->input_image_tokens,
                'output_tokens' => $this->output_tokens,
                'reasoning_tokens' => $this->reasoning_tokens,
            ],
            'image_url' => $hasImageExpired ? null : $this->image_url,
            'image_expired' => $hasImageExpired,
            'image_url_expires_at' => $this->image_url_expires_at?->toIso8601String(),
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
