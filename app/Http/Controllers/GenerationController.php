<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Generations\GenerationStatus;
use App\Http\Requests\Prompt\PromptDataRequest;
use App\Http\Resources\GenerationResource;
use App\Models\User;
use App\Services\Pixelforge\GenerationSubmitter;
use App\Services\Pixelforge\GenerationSynchroniser;
use App\Services\Pixelforge\Nanodollars;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GenerationController extends Controller
{
    public function index(Request $request, GenerationSynchroniser $synchroniser): Response
    {
        /** @var User $user */
        $user = $request->user();
        $synchroniser->refreshPendingFor($user);

        return Inertia::render('History', [
            'generations' => GenerationResource::collection(
                $user->generations()->latest()->latest('id')->get(),
            )->resolve(),
            'summary' => $this->summaryFor($user),
        ]);
    }

    public function store(PromptDataRequest $request, GenerationSubmitter $submitter): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $submitter->submit($user, $request->promptData());

        return back();
    }

    /**
     * @return array{spent_usd: string, succeeded_count: int, pending_count: int, pending_estimated_usd: string}
     */
    private function summaryFor(User $user): array
    {
        $succeeded = $user->generations()->where('status', GenerationStatus::Succeeded);
        $pending = $user->generations()->pending();

        return [
            'spent_usd' => Nanodollars::toUsd((int) $succeeded->clone()->sum('charged_nanodollars')),
            'succeeded_count' => $succeeded->clone()->count(),
            'pending_count' => $pending->clone()->count(),
            'pending_estimated_usd' => Nanodollars::toUsd((int) $pending->clone()->sum('estimated_cost_nanodollars')),
        ];
    }
}
