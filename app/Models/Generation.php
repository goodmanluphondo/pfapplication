<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Generations\GenerationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $pixelforge_id
 * @property string $idempotency_key
 * @property GenerationStatus $status
 * @property string $prompt
 * @property string $size
 * @property string $quality
 * @property list<string> $reference_image_urls
 * @property int $estimated_cost_nanodollars
 * @property int $estimated_minimum_cost_nanodollars
 * @property int $estimated_maximum_cost_nanodollars
 * @property array{input_text: array{estimated: int, minimum: int, maximum: int}, input_image: int, output: int, reasoning: array{minimum: int, maximum: int}} $estimated_tokens
 * @property int|null $input_text_tokens
 * @property int|null $input_image_tokens
 * @property int|null $output_tokens
 * @property int|null $reasoning_tokens
 * @property array<string, string>|null $rates
 * @property int|null $provider_cost_nanodollars
 * @property int|null $charged_nanodollars
 * @property string|null $image_url
 * @property CarbonImmutable|null $image_url_expires_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $last_synced_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'pixelforge_id',
    'idempotency_key',
    'status',
    'prompt',
    'size',
    'quality',
    'reference_image_urls',
    'estimated_cost_nanodollars',
    'estimated_minimum_cost_nanodollars',
    'estimated_maximum_cost_nanodollars',
    'estimated_tokens',
    'input_text_tokens',
    'input_image_tokens',
    'output_tokens',
    'reasoning_tokens',
    'rates',
    'provider_cost_nanodollars',
    'charged_nanodollars',
    'image_url',
    'image_url_expires_at',
    'error_code',
    'error_message',
    'started_at',
    'completed_at',
    'last_synced_at',
])]
final class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GenerationStatus::class,
            'reference_image_urls' => 'array',
            'estimated_tokens' => 'array',
            'rates' => 'array',
            'image_url_expires_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', GenerationStatus::pending());
    }

    public function isPending(): bool
    {
        return ! $this->status->isFinal();
    }

    public function hasImageExpired(): bool
    {
        return $this->image_url_expires_at !== null && $this->image_url_expires_at->isPast();
    }
}
