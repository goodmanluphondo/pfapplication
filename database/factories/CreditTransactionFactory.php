<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Credits\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
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
            'generation_id' => null,
            'type' => CreditTransactionType::Purchase,
            'amount_nanodollars' => 10_000_000_000,
        ];
    }

    public function purchaseOfDollars(int $dollars): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransactionType::Purchase,
            'amount_nanodollars' => $dollars * 1_000_000_000,
        ]);
    }
}
