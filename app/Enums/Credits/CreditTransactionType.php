<?php

declare(strict_types=1);

namespace App\Enums\Credits;

enum CreditTransactionType: string
{
    case Purchase = 'purchase';
    case GenerationHold = 'generation_hold';
    case GenerationHoldRelease = 'generation_hold_release';
    case GenerationCharge = 'generation_charge';
}
