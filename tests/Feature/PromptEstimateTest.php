<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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

test('it estimates the user price with the markup using provider pricing', function () {
    Cache::flush();
    Http::fake(['pixelforge.promptedit.dev/v1/pricing' => Http::response(pixelforgePricingResponse())]);

    $response = $this->postJson(route('prompt.estimate'), [
        'prompt' => 'test',
        'size' => '1024x1024',
        'quality' => 'medium',
        'reference_image_urls' => ['https://example.com/reference.png'],
    ]);

    $response->assertOk()->assertExactJson([
        'estimated_provider_cost_usd' => '0.065405000',
        'estimated_user_cost_usd' => '0.081756250',
    ]);
});

test('it rejects unsupported quality and more than four reference urls', function () {
    Cache::flush();
    Http::fake(['pixelforge.promptedit.dev/v1/pricing' => Http::response(pixelforgePricingResponse())]);

    $this->postJson(route('prompt.estimate'), [
        'prompt' => 'test',
        'size' => '1024x1024',
        'quality' => 'standard',
    ])->assertUnprocessable()->assertJsonValidationErrors('quality');

    $this->postJson(route('prompt.estimate'), [
        'prompt' => 'test',
        'size' => '1024x1024',
        'quality' => 'medium',
        'reference_image_urls' => array_fill(0, 5, 'https://example.com/reference.png'),
    ])->assertUnprocessable()->assertJsonValidationErrors('reference_image_urls');
});

test('it reports a temporary error when the pricing API is unavailable', function () {
    Cache::flush();
    Http::fake(fn () => Http::response([], 503));

    $this->postJson(route('prompt.estimate'), [
        'prompt' => 'test',
        'size' => '1024x1024',
        'quality' => 'medium',
    ])->assertServiceUnavailable()->assertJsonPath('message', 'Pricing is temporarily unavailable.');
});
