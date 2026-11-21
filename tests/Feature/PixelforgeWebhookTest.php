<?php

use App\Enums\Generations\GenerationStatus;
use App\Models\Generation;

beforeEach(function () {
    config(['services.pixelforge.webhook_secret' => WEBHOOK_SECRET]);
});

test('an event with an invalid signature is rejected', function () {
    $generation = Generation::factory()->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);

    sendPixelforgeEvent(succeededPixelforgeGeneration(), 'wrong-secret')->assertBadRequest();

    expect($generation->fresh()->status)->toBe(GenerationStatus::Queued);
});

test('a succeeded event records usage, the charge and the image', function () {
    $generation = Generation::factory()->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);

    sendPixelforgeEvent(succeededPixelforgeGeneration())->assertNoContent();

    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Succeeded)
        ->and($generation->reasoning_tokens)->toBe(842)
        ->and($generation->provider_cost_nanodollars)->toBe(54_570_000)
        ->and($generation->charged_nanodollars)->toBe(68_212_500)
        ->and($generation->image_url)->toStartWith('https://pixelforge.promptedit.dev/images/');
});

test('a late processing event never moves a finished generation back', function () {
    $generation = Generation::factory()->succeeded()->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);

    sendPixelforgeEvent(pixelforgeGeneration(['status' => 'processing']))->assertNoContent();

    expect($generation->fresh()->status)->toBe(GenerationStatus::Succeeded);
});

test('an event that arrives before the create request returns is matched by its metadata', function () {
    $generation = Generation::factory()->create(['pixelforge_id' => null]);

    sendPixelforgeEvent(pixelforgeGeneration([
        'status' => 'failed',
        'metadata' => ['generation_id' => (string) $generation->id],
        'error' => ['code' => 'generation_failed', 'message' => "The model couldn't produce an image."],
    ]))->assertNoContent();

    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->pixelforge_id)->toBe('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K')
        ->and($generation->error_code)->toBe('generation_failed')
        ->and($generation->charged_nanodollars)->toBeNull();
});
