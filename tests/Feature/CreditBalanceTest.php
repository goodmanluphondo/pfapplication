<?php

use App\Enums\Credits\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use App\Services\Credits\CreditLedger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['inertia.ssr.enabled' => false, 'services.pixelforge.webhook_secret' => WEBHOOK_SECRET]);
    Cache::flush();
    Http::preventStrayRequests();
});

test('buying a credit pack adds it to the balance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('credits.purchase'), ['dollars' => 10])->assertSessionHasNoErrors();

    expect(app(CreditLedger::class)->balanceFor($user))->toBe(10_000_000_000);
});

test('only the offered credit packs can be bought', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('credits.purchase'), ['dollars' => 1000])
        ->assertSessionHasErrors(['dollars' => 'Choose one of the credit packs.']);

    expect(CreditTransaction::query()->count())->toBe(0);
});

test('guests cannot buy credits', function () {
    $this->post(route('credits.purchase'), ['dollars' => 5])->assertRedirect(route('login'));
});

test('a user without enough credit cannot start an image', function () {
    $user = User::factory()->create();
    Http::fake([PRICING_URL => Http::response(pixelforgePricingResponse())]);

    $this->actingAs($user)
        ->post(route('generations.store'), validPrompt())
        ->assertSessionHasErrors('credits');

    expect($user->generations()->count())->toBe(0);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/generations'));
});

test('starting an image holds its maximum cost until it finishes', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchaseOfDollars(5)->create();
    Http::fake([
        PRICING_URL => Http::response(pixelforgePricingResponse()),
        GENERATIONS_URL => Http::response(pixelforgeGeneration(), 202),
    ]);

    $this->actingAs($user)->post(route('generations.store'), validPrompt())->assertSessionHasNoErrors();

    $generation = $user->generations()->sole();

    expect(app(CreditLedger::class)->balanceFor($user))
        ->toBe(5_000_000_000 - $generation->estimated_maximum_cost_nanodollars);
});

test('a succeeded image releases its hold and charges the actual cost', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchaseOfDollars(5)->create();
    $generation = Generation::factory()->for($user)->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);
    app(CreditLedger::class)->holdFor($generation);

    sendPixelforgeEvent(succeededPixelforgeGeneration())->assertNoContent();
    sendPixelforgeEvent(succeededPixelforgeGeneration())->assertNoContent();

    $generation->refresh();

    expect(app(CreditLedger::class)->balanceFor($user))->toBe(5_000_000_000 - $generation->charged_nanodollars)
        ->and(CreditTransaction::query()->where('type', CreditTransactionType::GenerationCharge)->count())->toBe(1);
});

test('a failed image gives its hold back and charges nothing', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->purchaseOfDollars(5)->create();
    $generation = Generation::factory()->for($user)->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);
    app(CreditLedger::class)->holdFor($generation);

    sendPixelforgeEvent(pixelforgeGeneration([
        'status' => 'failed',
        'error' => ['code' => 'internal_error', 'message' => 'Failed.'],
    ]))->assertNoContent();

    expect(app(CreditLedger::class)->balanceFor($user))->toBe(5_000_000_000);
});
