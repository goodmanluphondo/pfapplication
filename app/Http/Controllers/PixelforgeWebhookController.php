<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Services\Pixelforge\GenerationSynchroniser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PixelforgeWebhookController extends Controller
{
    private const int SIGNATURE_TOLERANCE_SECONDS = 300;

    public function __invoke(Request $request, GenerationSynchroniser $synchroniser): Response
    {
        $payload = $request->getContent();

        abort_unless($this->hasValidSignature($payload, (string) $request->header('Pixelforge-Signature')), 400, 'Invalid Pixelforge signature.');

        $event = json_decode($payload, true);
        $pixelforgeGeneration = is_array($event) ? ($event['data']['object'] ?? null) : null;

        if (! is_array($pixelforgeGeneration) || ($pixelforgeGeneration['object'] ?? null) !== 'generation') {
            return response()->noContent();
        }

        $generation = $this->findGeneration($pixelforgeGeneration);

        if ($generation !== null) {
            $synchroniser->apply($generation, $pixelforgeGeneration);
        }

        return response()->noContent();
    }

    private function hasValidSignature(string $payload, string $signatureHeader): bool
    {
        $secret = (string) config('services.pixelforge.webhook_secret');
        $signature = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $signature[trim($key)] = trim($value);
        }

        $timestamp = (int) ($signature['t'] ?? 0);
        $expected = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $secret !== ''
            && hash_equals($expected, $signature['v1'] ?? '')
            && abs(time() - $timestamp) <= self::SIGNATURE_TOLERANCE_SECONDS;
    }

    /**
     * @param  array<string, mixed>  $pixelforgeGeneration
     */
    private function findGeneration(array $pixelforgeGeneration): ?Generation
    {
        $localGenerationId = $pixelforgeGeneration['metadata']['generation_id'] ?? null;

        return Generation::query()->where('pixelforge_id', (string) ($pixelforgeGeneration['id'] ?? ''))->first()
            ?? (is_numeric($localGenerationId) ? Generation::query()->whereKey((int) $localGenerationId)->first() : null);
    }
}
