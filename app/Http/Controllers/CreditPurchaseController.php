<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Credits\CreditPurchaseRequest;
use App\Models\User;
use App\Services\Credits\CreditLedger;
use App\Services\Pixelforge\Nanodollars;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class CreditPurchaseController extends Controller
{
    public function __invoke(CreditPurchaseRequest $request, CreditLedger $ledger): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $dollars = $request->dollars();

        $ledger->purchase($user, Nanodollars::fromWholeDollars($dollars));

        Inertia::flash('toast', ['type' => 'success', 'message' => sprintf('Added $%d.00 in credits.', $dollars)]);

        return back();
    }
}
