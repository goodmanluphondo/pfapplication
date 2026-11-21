<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use App\Models\Generation;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class GenerationSubmitter
{
    public const int MAXIMUM_PENDING_GENERATIONS = 3;

    public function __construct(
        private GenerationPriceEstimator $estimator,
        private PixelforgeClient $client,
        private GenerationSynchroniser $synchroniser,
    ) {}

    /**
     * @param  array{prompt: string, size: string, quality: string, reference_image_urls?: list<string>|null}  $data
     *
     * @throws ValidationException
     */
    public function submit(User $user, array $data): Generation
    {
        if ($user->generations()->pending()->count() >= self::MAXIMUM_PENDING_GENERATIONS) {
            throw $this->rejection('You already have 3 images generating. Try again when one finishes.');
        }

        $referenceImageUrls = $data['reference_image_urls'] ?? [];
        $estimate = $this->estimate([...$data, 'reference_image_urls' => $referenceImageUrls]);

        $generation = $user->generations()->create([
            'idempotency_key' => (string) Str::uuid(),
            'prompt' => $data['prompt'],
            'size' => $data['size'],
            'quality' => $data['quality'],
            'reference_image_urls' => $referenceImageUrls,
            'estimated_cost_nanodollars' => $estimate->userCost,
            'estimated_minimum_cost_nanodollars' => $estimate->minimumUserCost,
            'estimated_maximum_cost_nanodollars' => $estimate->maximumUserCost,
            'estimated_tokens' => $estimate->toArray()['tokens'],
        ]);

        try {
            $pixelforgeGeneration = $this->client->createGeneration([
                'prompt' => $generation->prompt,
                'size' => $generation->size,
                'quality' => $generation->quality,
                'reference_images' => $referenceImageUrls,
                'metadata' => ['generation_id' => (string) $generation->id],
            ], $generation->idempotency_key);
        } catch (PixelforgeRequestException $exception) {
            $generation->delete();

            throw $this->rejection($this->messageFor($exception->errorCode));
        }

        $this->synchroniser->apply($generation, $pixelforgeGeneration);

        return $generation;
    }

    /**
     * @param  array{prompt: string, size: string, quality: string, reference_image_urls: list<string>}  $data
     */
    private function estimate(array $data): GenerationPriceEstimate
    {
        try {
            return $this->estimator->estimate($data);
        } catch (ConnectionException|RequestException|RuntimeException) {
            throw $this->rejection('Pricing is temporarily unavailable, so we could not start this image. Try again shortly.');
        }
    }

    private function messageFor(string $errorCode): string
    {
        return match ($errorCode) {
            'content_policy_violation' => "This prompt was rejected by Pixelforge's content policy. You weren't charged.",
            'concurrency_limit_exceeded' => 'Too many images are generating right now. Try again when one finishes.',
            'daily_limit_exceeded' => "Today's generation limit has been reached. Try again tomorrow.",
            'rate_limit_exceeded' => 'Too many requests right now. Try again in a minute.',
            'overloaded' => 'Pixelforge is busy right now. Try again in a moment.',
            default => "We couldn't start this image. Try again in a moment.",
        };
    }

    private function rejection(string $message): ValidationException
    {
        return ValidationException::withMessages(['prompt' => $message]);
    }
}
