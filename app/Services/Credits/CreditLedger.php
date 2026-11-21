<?php

declare(strict_types=1);

namespace App\Services\Credits;

use App\Enums\Credits\CreditTransactionType;
use App\Enums\Generations\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreditLedger
{
    public function balanceFor(User $user): int
    {
        return (int) CreditTransaction::query()->where('user_id', $user->id)->sum('amount_nanodollars');
    }

    public function purchase(User $user, int $amountNanodollars): CreditTransaction
    {
        return CreditTransaction::query()->create([
            'user_id' => $user->id,
            'type' => CreditTransactionType::Purchase,
            'amount_nanodollars' => $amountNanodollars,
        ]);
    }

    public function lockAccount(User $user): void
    {
        User::query()->whereKey($user->id)->lockForUpdate()->first();
    }

    public function holdFor(Generation $generation): void
    {
        CreditTransaction::query()->create([
            'user_id' => $generation->user_id,
            'generation_id' => $generation->id,
            'type' => CreditTransactionType::GenerationHold,
            'amount_nanodollars' => -$generation->estimated_maximum_cost_nanodollars,
        ]);
    }

    public function settle(Generation $generation): void
    {
        if (! $generation->status->isFinal()) {
            return;
        }

        DB::transaction(function () use ($generation): void {
            $hold = CreditTransaction::query()
                ->where('generation_id', $generation->id)
                ->where('type', CreditTransactionType::GenerationHold)
                ->first();

            if ($hold !== null) {
                CreditTransaction::query()->firstOrCreate(
                    ['generation_id' => $generation->id, 'type' => CreditTransactionType::GenerationHoldRelease],
                    ['user_id' => $generation->user_id, 'amount_nanodollars' => -$hold->amount_nanodollars],
                );
            }

            if ($generation->status === GenerationStatus::Succeeded && $generation->charged_nanodollars !== null) {
                CreditTransaction::query()->firstOrCreate(
                    ['generation_id' => $generation->id, 'type' => CreditTransactionType::GenerationCharge],
                    ['user_id' => $generation->user_id, 'amount_nanodollars' => -$generation->charged_nanodollars],
                );
            }
        });
    }
}
