<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

final class PixelforgeClient
{
    private const int MAXIMUM_OVERLOADED_ATTEMPTS = 3;

    private const int MAXIMUM_RETRY_AFTER_SECONDS = 5;

    private const int HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * @param  array{prompt: string, size: string, quality: string, reference_images: list<string>, metadata: array<string, string>}  $parameters
     * @return array<string, mixed>
     */
    public function createGeneration(array $parameters, string $idempotencyKey): array
    {
        for ($attempt = 1; ; $attempt++) {
            $response = $this->send(fn (PendingRequest $request) => $request
                ->withHeader('Idempotency-Key', $idempotencyKey)
                ->post('/generations', $parameters));

            $isOverloaded = $response->status() === self::HTTP_SERVICE_UNAVAILABLE;

            if (! $isOverloaded || $attempt >= self::MAXIMUM_OVERLOADED_ATTEMPTS) {
                return $this->generationFrom($response);
            }

            Sleep::for(min((int) $response->header('Retry-After') ?: 1, self::MAXIMUM_RETRY_AFTER_SECONDS))->seconds();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveGeneration(string $pixelforgeId): array
    {
        return $this->generationFrom(
            $this->send(fn (PendingRequest $request) => $request->get('/generations/'.rawurlencode($pixelforgeId))),
        );
    }

    /**
     * @param  callable(PendingRequest): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            return $request(
                Http::baseUrl(rtrim((string) config('services.pixelforge.base_url'), '/'))
                    ->withToken((string) config('services.pixelforge.api_key'))
                    ->acceptJson()
                    ->connectTimeout(3)
                    ->timeout(10),
            );
        } catch (ConnectionException $exception) {
            throw new PixelforgeRequestException(PixelforgeRequestException::CONNECTION_FAILED, 0, $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function generationFrom(Response $response): array
    {
        $body = $response->json();

        if ($response->successful() && is_array($body) && ($body['object'] ?? null) === 'generation') {
            return $body;
        }

        throw new PixelforgeRequestException(
            (string) ($body['error']['code'] ?? 'unexpected_response'),
            $response->status(),
            (string) ($body['error']['message'] ?? 'Pixelforge returned an unexpected response.'),
        );
    }
}
