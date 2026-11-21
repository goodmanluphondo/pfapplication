<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function pixelforgePricingResponse(): array
{
    return [
        'object' => 'pricing',
        'currency' => 'usd',
        'unit' => 'per_1m_tokens',
        'rates' => [
            'input_text_tokens' => '5.00',
            'input_image_tokens' => '10.00',
            'output_tokens' => '40.00',
            'reasoning_tokens' => '12.50',
        ],
        'tokens' => [
            'characters_per_text_token' => 4,
            'text_token_variance' => 0.15,
            'per_reference_image' => 765,
            'output' => [
                'low' => ['1024x1024' => 280, '1024x1536' => 420, '1536x1024' => 420],
                'medium' => ['1024x1024' => 1100, '1024x1536' => 1650, '1536x1024' => 1650],
                'high' => ['1024x1024' => 4300, '1024x1536' => 6450, '1536x1024' => 6450],
            ],
            'reasoning' => [
                'low' => ['min' => 0, 'max' => 500],
                'medium' => ['min' => 200, 'max' => 2000],
                'high' => ['min' => 1000, 'max' => 8000],
            ],
        ],
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pixelforgeGeneration(array $overrides = []): array
{
    return [
        'id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K',
        'object' => 'generation',
        'status' => 'queued',
        'prompt' => 'A lighthouse on a cliff at dawn',
        'size' => '1024x1024',
        'quality' => 'medium',
        'reference_images' => [],
        'metadata' => [],
        'image_url' => null,
        'image_url_expires_at' => null,
        'usage' => null,
        'rates' => pixelforgePricingResponse()['rates'],
        'error' => null,
        'created_at' => '2026-11-21T10:00:00Z',
        'started_at' => null,
        'completed_at' => null,
        ...$overrides,
    ];
}

/**
 * @return array<string, mixed>
 */
function succeededPixelforgeGeneration(): array
{
    return pixelforgeGeneration([
        'status' => 'succeeded',
        'image_url' => 'https://pixelforge.promptedit.dev/images/gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K.png?signature=abc',
        'image_url_expires_at' => '2026-11-22T10:00:20Z',
        'usage' => [
            'input_text_tokens' => 9,
            'input_image_tokens' => 0,
            'output_tokens' => 1100,
            'reasoning_tokens' => 842,
            'total_tokens' => 1951,
        ],
        'started_at' => '2026-11-21T10:00:02Z',
        'completed_at' => '2026-11-21T10:00:20Z',
    ]);
}

const PRICING_URL = 'pixelforge.promptedit.dev/v1/pricing';
const GENERATIONS_URL = 'pixelforge.promptedit.dev/v1/generations';

const WEBHOOK_SECRET = 'whsec_test';

function validPrompt(array $overrides = []): array
{
    return [
        'prompt' => 'A lighthouse on a cliff at dawn',
        'size' => '1024x1024',
        'quality' => 'medium',
        'reference_image_urls' => [],
        ...$overrides,
    ];
}

function sendPixelforgeEvent(array $pixelforgeGeneration, ?string $secret = WEBHOOK_SECRET)
{
    $payload = json_encode([
        'id' => 'evt_01JC2Z9B7K2M4P6R8T0V3X5Z7A',
        'object' => 'event',
        'type' => 'generation.'.$pixelforgeGeneration['status'],
        'data' => ['object' => $pixelforgeGeneration],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", (string) $secret);

    return test()->call('POST', route('webhooks.pixelforge'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_PIXELFORGE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], $payload);
}
