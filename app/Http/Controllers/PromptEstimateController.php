<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Prompt\PromptDataRequest;
use App\Services\Pixelforge\GenerationPriceEstimator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class PromptEstimateController
{
    public function __invoke(PromptDataRequest $request, GenerationPriceEstimator $estimator): JsonResponse
    {
        try {
            return response()->json($estimator->estimate($request->validated()));
        } catch (ConnectionException|RequestException|RuntimeException) {
            return response()->json(['message' => 'Pricing is temporarily unavailable.'], 503);
        }
    }
}
