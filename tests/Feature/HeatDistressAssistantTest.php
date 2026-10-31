<?php

use App\Models\PointFraicheur;
use App\Models\User;
use App\Services\CoolingPointFinder;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

function heatIntent(array $overrides = []): array
{
    return ['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode(array_replace([
        'location' => 'Avenue Habib Bourguiba, Tunis', 'needs_accessible' => false, 'emergency' => false,
    ], $overrides))]]]];
}

function heatPlace(array $attributes = []): PointFraicheur
{
    return PointFraicheur::factory()->create(array_replace([
        'latitude' => 36.801, 'longitude' => 10.18, 'horaires' => '24/7', 'eau_potable' => true, 'ombre' => true,
    ], $attributes));
}

function heatGps(array $attributes = []): array
{
    return array_replace(['language' => 'fr', 'message' => 'Je cherche de l’eau et de l’ombre',
        'latitude' => 36.80, 'longitude' => 10.18, 'accuracy' => 20], $attributes);
}

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
    Http::preventStrayRequests();
    config(['heat_assistant.key' => 'member-three-test-secret', 'heat_assistant.daily_limit' => 100]);
    $this->endpoint = route('cooling-points.assistant');
});

test('cooling page offers labelled multilingual voice and text controls without sending requests', function () {
    $this->get(route('cooling-points'))->assertOk()->assertSee('data-heat-record', false)
        ->assertSee('data-heat-message', false)->assertSee('Derja')->assertSee('aria-live="polite"', false)
        ->assertDontSee('member-three-test-secret');
    Http::assertNothingSent();
});

test('nearest selection uses actual distance and excludes closed inactive unconfirmed missing and far points', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent())]);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 12:00:00', 'Africa/Tunis'));
    $nearest = heatPlace(['nom' => 'Nearest drinking water']);
    heatPlace(['latitude' => 36.82]);
    foreach ([['actif' => false], ['eau_potable' => false], ['ombre' => false], ['horaires' => null],
        ['horaires' => '10:00 - 11:00'], ['horaires' => 'Weekends only'], ['latitude' => null], ['longitude' => null]] as $attributes) {
        heatPlace(array_replace(['latitude' => 36.80], $attributes));
    }
    $this->postJson($this->endpoint, heatGps(['point_id' => 999, 'distance_km' => 0]))
        ->assertOk()->assertJsonPath('status', 'found')->assertJsonPath('point.id', $nearest->id)
        ->assertJsonPath('choices', [])->assertJsonPath('text', fn ($text) => str_contains($text, 'vol d’oiseau'))->assertHeader('Cache-Control', 'no-store, private');
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer member-three-test-secret')
        && $request['response_format']['json_schema']['strict'] === true
        && ! str_contains($request['messages'][1]['content'], '36.8'));
    PointFraicheur::query()->update(['actif' => false]);
    heatPlace(['latitude' => 37.5]);
    $this->postJson($this->endpoint, heatGps())->assertOk()->assertJsonPath('status', 'no_match')->assertJsonPath('point', null);
    CarbonImmutable::setTestNow();
});

test('PMR requests both explicit and interpreted exclude inaccessible closer locations', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent(['needs_accessible' => true]))]);
    heatPlace(['accessible_pmr' => false]);
    $accessible = heatPlace(['latitude' => 36.81, 'accessible_pmr' => true]);
    $this->postJson($this->endpoint, heatGps(['accessible' => true]))->assertOk()->assertJsonPath('point.id', $accessible->id);
    $this->postJson($this->endpoint, heatGps())->assertOk()->assertJsonPath('point.id', $accessible->id);
});

test('known hours use Tunis time with overnight ranges and exclude ambiguous hours', function () {
    $finder = app(CoolingPointFinder::class);
    $night = CarbonImmutable::parse('2026-10-31 01:00', 'Africa/Tunis');
    foreach (['24/7', '24h/24', 'always open', '22:00 - 06:00'] as $hours) {
        expect($finder->isOpen($hours, $night))->toBeTrue();
    }
    foreach ([null, '', 'closed', 'Monday 00:00 - 05:00', '25:00 - 26:00', '00:00 - 00:00', '08:00 - 18:00'] as $hours) {
        expect($finder->isOpen($hours, $night))->toBeFalse();
    }
    expect($finder->isOpen('08:00 - 18:00', $night->setTime(18, 0)))->toBeFalse()
        ->and($finder->isOpen('08:00 - 18:00', $night->setTime(8, 0)))->toBeTrue();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-31 07:30:00', 'UTC'));
    heatPlace(['horaires' => '08:00 - 09:00']);
    expect($finder->nearest(36.80, 10.18, false))->not->toBeNull();
    CarbonImmutable::setTestNow();
});

test('spoken street always requires confirmation and a signed origin rechecks current places without another AI call', function () {
    $point = heatPlace();
    Http::fake(['api.groq.com/*' => Http::response(heatIntent()), 'photon.komoot.io/*' => Http::response(['features' => [
        ['properties' => ['name' => 'Avenue Habib Bourguiba', 'city' => 'Tunis', 'countrycode' => 'TN'],
            'geometry' => ['coordinates' => [10.18, 36.80]]],
        ['properties' => ['name' => 'Wrong country', 'countrycode' => 'FR'], 'geometry' => ['coordinates' => [2.3, 48.8]]],
    ]])]);
    $request = ['message' => 'راني دايخ في نهج الحبيب بورقيبة في تونس', 'language' => 'derja'];
    $response = $this->postJson($this->endpoint, $request)->assertOk()->assertJsonPath('status', 'confirm_location')
        ->assertJsonCount(1, 'choices')->assertJsonPath('point', null);
    $token = $response->json('choices.0.token');
    $this->postJson($this->endpoint, ['language' => 'derja', 'location_token' => $token])
        ->assertOk()->assertJsonPath('point.id', $point->id)->assertJsonPath('language', 'derja');
    $point->update(['actif' => false]);
    $this->postJson($this->endpoint, ['language' => 'fr', 'location_token' => $token])
        ->assertOk()->assertJsonPath('status', 'no_match');
    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'photon.komoot.io') && $request['countrycode'] === 'TN'
        && $request['q'] === 'Avenue Habib Bourguiba, Tunis' && ! str_contains($request->url(), 'دايخ'));
    $this->postJson($this->endpoint, ['language' => 'fr', 'location_token' => 'forged'])->assertUnprocessable();
    $this->travel(6)->minutes();
    $this->postJson($this->endpoint, ['language' => 'fr', 'location_token' => $token])->assertUnprocessable();
    $this->travelBack();
});

test('missing or unknown spoken locations never substitute a default city position', function () {
    heatPlace();
    Http::fake(['api.groq.com/*' => Http::response(heatIntent(['location' => '']))]);
    $this->postJson($this->endpoint, ['language' => 'ar', 'message' => 'أين أجد ماء'])
        ->assertOk()->assertJsonPath('status', 'needs_location')->assertJsonPath('point', null);
    Http::assertSentCount(1);
});

test('geocoder discards invalid places and caches empty results without a default location', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent()), 'photon.komoot.io/*' => Http::response([
        'features' => ['malformed', ['properties' => ['name' => 'Invalid', 'countrycode' => 'TN'],
            'geometry' => ['coordinates' => [999, 999]]]],
    ])]);
    $request = ['language' => 'fr', 'message' => 'Je suis avenue Habib Bourguiba à Tunis'];
    $this->postJson($this->endpoint, $request)->assertOk()->assertJsonPath('status', 'needs_location');
    $this->postJson($this->endpoint, $request)->assertOk()->assertJsonPath('status', 'needs_location');
    Http::assertSentCount(3); // Two interpretations, only one geocoder request.
});

test('geocoder failure is handled and exposes no upstream body', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent()), 'photon.komoot.io/*' => Http::response('provider private error', 503)]);
    $this->postJson($this->endpoint, ['language' => 'fr', 'message' => 'Je suis à Tunis'])
        ->assertServiceUnavailable()->assertDontSee('provider private error');
});

test('emergency wording bypasses providers and normal recommendations even without configuration', function () {
    config(['heat_assistant.key' => '']);
    foreach (['Je suis confus et je ne peux pas marcher', 'مش قادر نتنفس', 'I cannot breathe'] as $message) {
        $this->postJson($this->endpoint, ['message' => $message, 'language' => 'fr'])
            ->assertOk()->assertJsonPath('status', 'emergency')->assertJsonPath('point', null);
    }
    Http::assertNothingSent();
});

test('AI emergency interpretation returns fixed instructions without geocoding', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent(['emergency' => true]))]);
    $this->postJson($this->endpoint, heatGps())->assertOk()->assertJsonPath('status', 'emergency')->assertJsonPath('point', null);
    Http::assertSentCount(1);
});

test('invalid coordinates oversized text and incomplete GPS are rejected before provider access', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    foreach ([['message' => ''], ['language' => 'zz'], ['latitude' => 91], ['longitude' => 181],
        ['accuracy' => 201], ['longitude' => null], ['message' => str_repeat('a', 1001)]] as $override) {
        $this->postJson($this->endpoint, heatGps($override))->assertUnprocessable();
    }
    Http::assertNothingSent();
});

test('provider malformed output network errors quotas and missing keys fail without leaking secrets', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    Http::fake(['api.groq.com/*' => Http::sequence()
        ->push(['error' => 'upstream secret'], 500)->push(['error' => 'upstream secret'], 429)
        ->push(heatIntent(['location' => 5]))]);
    foreach ([503, 429, 503] as $status) {
        $this->postJson($this->endpoint, heatGps())->assertStatus($status)->assertDontSee('upstream secret')->assertDontSee('member-three-test-secret');
    }
    config(['heat_assistant.key' => '']);
    $this->postJson($this->endpoint, heatGps())->assertServiceUnavailable();
});

test('connection failures never expose private transport details', function () {
    Http::fake(fn () => throw new ConnectionException('private transport details'));
    $this->postJson($this->endpoint, heatGps())->assertServiceUnavailable()->assertDontSee('private transport details');
});

test('separate daily budget and per-client throttles limit public AI usage', function () {
    Http::fake(['api.groq.com/*' => Http::response(heatIntent())]);
    config(['heat_assistant.daily_limit' => 1]);
    $this->postJson($this->endpoint, heatGps())->assertOk();
    $this->postJson($this->endpoint, heatGps())->assertTooManyRequests();
    Http::assertSentCount(1);
    Cache::flush();
    config(['heat_assistant.daily_limit' => 100]);
    for ($i = 0; $i < 10; $i++) {
        $this->postJson($this->endpoint, heatGps())->assertOk();
    }
    $this->postJson($this->endpoint, heatGps())->assertTooManyRequests();
});

test('audio validation and transcription use multipart Whisper without saving recordings', function () {
    $endpoint = route('cooling-points.assistant.transcribe');
    $this->postJson($endpoint, ['language' => 'fr'])->assertUnprocessable();
    $this->postJson($endpoint, ['language' => 'fr', 'audio' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])->assertUnprocessable();
    $this->postJson($endpoint, ['language' => 'fr', 'audio' => UploadedFile::fake()->create('large.wav', 4097, 'audio/wav')])->assertUnprocessable();
    Http::assertNothingSent();
    $multipartBody = '';
    $calls = 0;
    Http::fake(function ($request) use (&$multipartBody, &$calls) {
        // Inspect before the service closes its upload stream after the response.
        expect($request->isMultipart())->toBeTrue();
        $multipartBody = $request->body();

        return Http::response(++$calls === 1
            ? ['text' => 'راني في تونس', 'segments' => [['no_speech_prob' => 0.01]]]
            : ['text' => 'Hallucinated speech', 'segments' => [['no_speech_prob' => 0.99]]]);
    });
    $this->postJson($endpoint, ['language' => 'derja', 'audio' => UploadedFile::fake()->create('voice.webm', 100, 'audio/webm')])
        ->assertOk()->assertJsonPath('text', 'راني في تونس')->assertHeader('Cache-Control', 'no-store, private');
    expect($multipartBody)->toContain('whisper-large-v3')->not->toContain('name="language"');
    $this->postJson($endpoint, ['language' => 'fr', 'audio' => UploadedFile::fake()->create('silent.wav', 10, 'audio/wav')])->assertUnprocessable();
});

test('admin explicitly confirms water and shade and existing points default to unconfirmed', function () {
    $point = PointFraicheur::factory()->create();
    expect($point->fresh()->eau_potable)->toBeFalse()->and($point->fresh()->ombre)->toBeFalse();
    $data = $point->only(['nom', 'adresse', 'type_point_id', 'quartier_id']);
    $this->actingAs(User::factory()->create(['role' => 'ADMIN']))
        ->put(route('admin.point-fraicheurs.update', $point), $data + ['eau_potable' => 1, 'ombre' => 1])->assertRedirect();
    expect($point->fresh()->eau_potable)->toBeTrue()->and($point->fresh()->ombre)->toBeTrue();
    $this->put(route('admin.point-fraicheurs.update', $point), $data + ['eau_potable' => 0, 'ombre' => 0])->assertRedirect();
    expect($point->fresh()->eau_potable)->toBeFalse()->and($point->fresh()->ombre)->toBeFalse();
});
