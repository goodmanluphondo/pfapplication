<?php

use App\Enums\Generations\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    config(['inertia.ssr.enabled' => false]);
    Cache::flush();
    Http::preventStrayRequests();
});

test('guests are sent to log in instead of generating', function () {
    $this->post(route('generations.store'), validPrompt())->assertRedirect(route('login'));

    expect(Generation::query()->count())->toBe(0);
});

test('a prompt is submitted to Pixelforge and saved with its estimate', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->create();
    Http::fake([
        PRICING_URL => Http::response(pixelforgePricingResponse()),
        GENERATIONS_URL => Http::response(pixelforgeGeneration(), 202),
    ]);

    $this->actingAs($user)
        ->post(route('generations.store'), validPrompt(['reference_image_urls' => ['https://example.com/style.png']]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $generation = $user->generations()->sole();

    expect($generation->status)->toBe(GenerationStatus::Queued)
        ->and($generation->pixelforge_id)->toBe('gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K')
        ->and($generation->estimated_cost_nanodollars)->toBe(81_800_000)
        ->and($generation->reference_image_urls)->toBe(['https://example.com/style.png']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://'.GENERATIONS_URL
        && $request->hasHeader('Idempotency-Key', $generation->idempotency_key)
        && $request['reference_images'] === ['https://example.com/style.png']
        && $request['metadata'] === ['generation_id' => (string) $generation->id]);
});

test('a prompt is rejected when it is missing', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('generations.store'), validPrompt(['prompt' => '']))
        ->assertSessionHasErrors(['prompt' => 'The prompt field is required.']);
});

test('a fourth image cannot start while three are generating', function () {
    $user = User::factory()->create();
    Generation::factory()->for($user)->count(2)->create();
    Generation::factory()->for($user)->processing()->create();

    $this->actingAs($user)
        ->post(route('generations.store'), validPrompt())
        ->assertSessionHasErrors(['prompt' => 'You already have 3 images generating. Try again when one finishes.']);

    expect($user->generations()->count())->toBe(3);
    Http::assertNothingSent();
});

test('a prompt rejected by the content policy is not kept', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->create();
    Http::fake([
        PRICING_URL => Http::response(pixelforgePricingResponse()),
        GENERATIONS_URL => Http::response(['error' => [
            'type' => 'invalid_request_error',
            'code' => 'content_policy_violation',
            'message' => 'The prompt was rejected.',
        ]], 400),
    ]);

    $this->actingAs($user)
        ->post(route('generations.store'), validPrompt(['prompt' => 'Something [blocked]']))
        ->assertSessionHasErrors(['prompt' => "This prompt was rejected by Pixelforge's content policy. You weren't charged."]);

    expect($user->generations()->count())->toBe(0);
});

test('an overloaded Pixelforge is retried with the same idempotency key', function () {
    Sleep::fake();
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->create();
    Http::fake([
        PRICING_URL => Http::response(pixelforgePricingResponse()),
        GENERATIONS_URL => Http::sequence()
            ->push(['error' => ['code' => 'overloaded', 'message' => 'Overloaded.']], 503, ['Retry-After' => '2'])
            ->push(pixelforgeGeneration(), 202),
    ]);

    $this->actingAs($user)->post(route('generations.store'), validPrompt())->assertSessionHasNoErrors();

    $generation = $user->generations()->sole();
    $creationRequests = Http::recorded(fn (Request $request) => $request->url() === 'https://'.GENERATIONS_URL);

    expect($creationRequests)->toHaveCount(2)
        ->and($creationRequests->every(fn (array $pair) => $pair[0]->header('Idempotency-Key')[0] === $generation->idempotency_key))->toBeTrue();
    Sleep::assertSlept(fn ($duration) => $duration->totalSeconds === 2.0, 1);
});

test('history lists only your generations and refreshes ones still generating', function () {
    $user = User::factory()->create();
    $pending = Generation::factory()->for($user)->create(['pixelforge_id' => 'gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K']);
    Generation::factory()->for($user)->succeeded()->create(['created_at' => now()->subHour()]);
    Generation::factory()->create();
    Http::fake([GENERATIONS_URL.'/gen_01JC2Z8Q4N6V0M3H7X5T9R2B1K' => Http::response(succeededPixelforgeGeneration())]);

    $this->actingAs($user)
        ->get(route('generations.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History')
            ->has('generations', 2)
            ->where('generations.0.id', $pending->id)
            ->where('generations.0.status', 'succeeded')
            ->where('generations.0.charged_usd', '0.068212500')
            ->where('summary.spent_usd', '0.136431250')
            ->where('summary.succeeded_count', 2)
            ->where('summary.pending_count', 0));
});

test('history hides image links once they have expired', function () {
    $user = User::factory()->create();
    Generation::factory()->for($user)->succeeded()->create(['image_url_expires_at' => now()->subMinute()]);

    $this->actingAs($user)
        ->get(route('generations.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('generations.0.image_url', null)
            ->where('generations.0.image_expired', true));
});

test('a failed generation can be retried with the same settings', function () {
    $user = User::factory()->create();
    CreditTransaction::factory()->for($user)->create();
    $failed = Generation::factory()->for($user)->failed()->create([
        'prompt' => 'Retry me',
        'quality' => 'high',
        'reference_image_urls' => ['https://example.com/style.png'],
    ]);
    Http::fake([
        PRICING_URL => Http::response(pixelforgePricingResponse()),
        GENERATIONS_URL => Http::response(pixelforgeGeneration(['id' => 'gen_RETRY']), 202),
    ]);

    $this->actingAs($user)->post(route('generations.retry', $failed))->assertSessionHasNoErrors();

    $retried = $user->generations()->where('pixelforge_id', 'gen_RETRY')->sole();

    expect($retried->prompt)->toBe('Retry me')
        ->and($retried->quality)->toBe('high')
        ->and($retried->reference_image_urls)->toBe(['https://example.com/style.png']);
});

test("someone else's generation cannot be retried", function () {
    $failed = Generation::factory()->failed()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('generations.retry', $failed))
        ->assertNotFound();
});
