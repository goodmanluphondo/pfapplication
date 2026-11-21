<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

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
        'minimum_user_cost_usd' => '0.067687500',
        'maximum_user_cost_usd' => '0.095825000',
        'per_reference_image_user_cost_usd' => '0.009562500',
        'tokens' => [
            'input_text' => ['estimated' => 1, 'minimum' => 0, 'maximum' => 2],
            'input_image' => 765,
            'output' => 1100,
            'reasoning' => ['minimum' => 200, 'maximum' => 2000],
        ],
    ]);
});

test('it estimates the base cost before a prompt is typed', function () {
    Cache::flush();
    Http::fake(['pixelforge.promptedit.dev/v1/pricing' => Http::response(pixelforgePricingResponse())]);

    $this->postJson(route('prompt.estimate'), [
        'prompt' => '',
        'size' => '1024x1024',
        'quality' => 'medium',
    ])->assertOk()
        ->assertJsonPath('estimated_user_cost_usd', '0.072187500')
        ->assertJsonPath('tokens.input_text.estimated', 0);
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
