<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Generations\GenerationStatus;
use App\Models\Generation;
use App\Models\User;
use App\Services\Pixelforge\GenerationSubmitter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class GenerationRetryController extends Controller
{
    public function __invoke(Request $request, Generation $generation, GenerationSubmitter $submitter): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($generation->user_id === $user->id, 404);
        abort_unless($generation->status === GenerationStatus::Failed, 409);

        $retriedGeneration = $submitter->submit($user, [
            'prompt' => $generation->prompt,
            'size' => $generation->size,
            'quality' => $generation->quality,
            'reference_image_urls' => $generation->reference_image_urls,
        ]);

        Inertia::flash('retriedGenerationId', $retriedGeneration->id);

        return back();
    }
}
