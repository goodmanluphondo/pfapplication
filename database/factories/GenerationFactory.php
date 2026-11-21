<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Generations\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'pixelforge_id' => 'gen_'.Str::upper(Str::random(26)),
            'idempotency_key' => (string) Str::uuid(),
            'status' => GenerationStatus::Queued,
            'prompt' => fake()->sentence(),
            'size' => '1024x1024',
            'quality' => 'medium',
            'reference_image_urls' => [],
            'estimated_cost_nanodollars' => 72_656_250,
            'estimated_minimum_cost_nanodollars' => 57_500_000,
            'estimated_maximum_cost_nanodollars' => 86_250_000,
            'estimated_tokens' => [
                'input_text' => ['estimated' => 10, 'minimum' => 8, 'maximum' => 12],
                'input_image' => 0,
                'output' => 1100,
                'reasoning' => ['minimum' => 200, 'maximum' => 2000],
            ],
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Succeeded,
            'input_text_tokens' => 10,
            'input_image_tokens' => 0,
            'output_tokens' => 1100,
            'reasoning_tokens' => 842,
            'rates' => [
                'input_text_tokens' => '5.00',
                'input_image_tokens' => '10.00',
                'output_tokens' => '40.00',
                'reasoning_tokens' => '12.50',
            ],
            'provider_cost_nanodollars' => 54_575_000,
            'charged_nanodollars' => 68_218_750,
            'image_url' => 'https://pixelforge.promptedit.dev/images/example.png',
            'image_url_expires_at' => now()->addDay(),
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GenerationStatus::Failed,
            'error_code' => 'internal_error',
            'error_message' => 'Something went wrong on our side while generating. You were not charged; please retry.',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }
}
