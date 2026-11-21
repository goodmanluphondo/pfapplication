<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\GenerationResource;
use App\Services\Pixelforge\GenerationSynchroniser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    private const int RECENT_GENERATION_COUNT = 3;

    public function __invoke(Request $request, GenerationSynchroniser $synchroniser): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $synchroniser->refreshPendingFor($user);
        }

        return Inertia::render('Home', [
            'recentGenerations' => $user === null ? [] : GenerationResource::collection(
                $user->generations()->latest()->latest('id')->limit(self::RECENT_GENERATION_COUNT)->get(),
            )->resolve(),
        ]);
    }
}
