<?php

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
    RateLimiter::clear('alert-explainer:daily');
    config()->set('services.google', ['key' => 'test-secret-key', 'explain_model' => 'gemini-3.5-flash', 'explain_daily_limit' => 60]);
    Http::preventStrayRequests();
    Sleep::fake();
});

/** Seven days starting today. Maxima default to a heat peak on the third day followed by a calm, rainy one. */
function weekPayload(array $max = [31.2, 36.8, 42.4, 38.1, 29.6, 27.3, 26.0], array $rain = [0, 10, 0, 20, 70, 55, 5]): array
{
    $days = count($max);

    return ['daily' => [
        'time' => collect(range(0, $days - 1))->map(fn ($i) => now()->addDays($i)->toDateString())->all(),
        'weather_code' => array_fill(0, $days, 1),
        'temperature_2m_max' => $max,
        'temperature_2m_min' => array_map(fn ($value) => $value - 8.4, $max),
        'apparent_temperature_max' => array_map(fn ($value) => $value + 2.2, $max),
        'uv_index_max' => array_fill(0, $days, 8.4),
        'wind_speed_10m_max' => array_fill(0, $days, 22.0),
        'precipitation_probability_max' => $rain,
        'sunrise' => array_fill(0, $days, now()->format('Y-m-d').'T06:41'),
        'sunset' => array_fill(0, $days, now()->format('Y-m-d').'T17:23'),
        'relative_humidity_2m_mean' => array_fill(0, $days, 47),
    ]];
}

function weekReply(string $headline = 'Heat peaks on Thursday, then it cools down.', array $points = ['Stay inside on Thursday.', 'Rain brings relief on Saturday.']): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode(['headline' => $headline, 'points' => $points], JSON_UNESCAPED_UNICODE)]]]]]];
}

function fakeWeek(?array $weather = null, mixed $gemini = null): void
{
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake([
        'api.open-meteo.com/*' => Http::response($weather ?? weekPayload()),
        'generativelanguage.googleapis.com/*' => $gemini ?? Http::response(weekReply()),
    ]);
}

function summarisable(array $attributes = []): Quartier
{
    return Quartier::factory()->create($attributes + ['nom' => 'La Marsa', 'ville' => 'Tunis', 'latitude' => 36.88, 'longitude' => 10.32]);
}

function geminiRequest(): ?Request
{
    return Http::recorded(fn (Request $request) => str_contains($request->url(), 'generativelanguage'))->first()[0] ?? null;
}

test('it returns a short briefing of the week', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk()
        ->assertJson([
            'headline' => 'Heat peaks on Thursday, then it cools down.',
            'points' => ['Stay inside on Thursday.', 'Rain brings relief on Saturday.'],
            'lang' => 'en', 'cached' => false,
        ])->assertHeader('Cache-Control');
});

test('the model receives rounded forecast rows and the precomputed highlights', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();

    $facts = json_decode(geminiRequest()->data()['contents'][0]['parts'][0]['text'], true)['neighborhood_forecast'];

    expect($facts['neighborhood'])->toBe('La Marsa')
        ->and($facts['days'])->toHaveCount(7)
        ->and($facts['days'][2])->toMatchArray(['max_c' => 42, 'min_c' => 34, 'feels_like_max_c' => 45, 'uv_index' => 8, 'wind_max_kmh' => 20, 'humidity_percent' => 45, 'estimated_level' => 'Red'])
        ->and($facts['highlights']['hottest_day']['max_c'])->toBe(42)
        ->and($facts['highlights']['coolest_day']['max_c'])->toBe(26)
        ->and($facts['highlights']['days_above_green'])->toBe(3)
        ->and($facts['highlights']['rainy_days'])->toHaveCount(2)
        ->and($facts['highlights']['first_green_day_after_the_peak'])->toBe(now()->addDays(4)->englishDayOfWeek);
});

test('only public weather and alert data is sent, never the visitor or the key', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->actingAs(App\Models\User::factory()->create(['name' => 'Private Person', 'email' => 'private@example.test']))
        ->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();

    $request = geminiRequest();
    $sent = json_encode($request->data());

    expect($sent)->not->toContain('Private Person')->not->toContain('private@example.test')->not->toContain('test-secret-key')
        ->and($request->header('x-goog-api-key'))->toBe(['test-secret-key'])
        ->and($request->url())->toContain('/models/gemini-3.5-flash:generateContent')
        ->and($request->data()['systemInstruction']['parts'][0]['text'])->toContain('Do not invent numbers, clock times');
});

test('published current and upcoming alerts are included, drafts and old ones are not', function () {
    fakeWeek();
    $quartier = summarisable();
    $make = fn (string $titre, int $start, int $end, bool $published = true) => AlerteMeteo::factory()->for($quartier)->create([
        'titre' => $titre, 'niveau' => 'rouge', 'publiee' => $published, 'risque_coupure' => true,
        'date_debut' => now()->addDays($start)->toDateString(), 'date_fin' => now()->addDays($end)->toDateString(),
    ]);
    $make('Alert running now', 0, 1);
    $make('Alert next week', 3, 4);
    $make('Alert far away', 12, 13);
    $make('Alert already over', -9, -7);
    $make('Alert still a draft', 0, 1, false);

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();

    $facts = json_decode(geminiRequest()->data()['contents'][0]['parts'][0]['text'], true)['neighborhood_forecast'];
    $titles = collect($facts['published_alerts'])->pluck('title')->all();

    expect($titles)->toContain('Alert running now', 'Alert next week')
        ->not->toContain('Alert far away', 'Alert already over', 'Alert still a draft')
        ->and($facts['published_alerts'][0]['power_outage_risk'])->toBeTrue();
});

test('each language is requested with its own instruction', function (string $lang, string $expected) {
    fakeWeek();
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => $lang])->assertOk()->assertJsonPath('lang', $lang);

    expect(geminiRequest()->data()['systemInstruction']['parts'][0]['text'])->toContain($expected);
})->with([
    ['en', 'plain, simple English'],
    ['fr', 'en français simple'],
    ['ar', 'Modern Standard Arabic'],
    ['tn', 'Tunisian Arabic (Derja'],
]);

test('the same week in the same language is generated only once', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertJsonPath('cached', false);
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertJsonPath('cached', true);
    Http::assertSentCount(2); // the weather once and the AI once: the cache then serves the forecast and the text

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'fr'])->assertJsonPath('cached', false);
});

test('a tiny forecast refresh does not trigger a new generation but a real change does', function () {
    $quartier = summarisable();
    $forgetForecast = fn () => Cache::forget('open-meteo-detailed:'.hash('sha256', json_encode([round($quartier->latitude, 3), round($quartier->longitude, 3), 7])));

    fakeWeek(weekPayload([31.0, 37.0, 42.0, 38.0, 29.0, 27.0, 26.0]));
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertJsonPath('cached', false);

    // +0.2 °C everywhere stays on the same rounded values: the saved text is reused.
    $forgetForecast();
    fakeWeek(weekPayload([31.2, 37.2, 42.2, 38.2, 29.2, 27.2, 26.2]));
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertJsonPath('cached', true);

    // A genuinely different week is written again.
    $forgetForecast();
    fakeWeek(weekPayload([25.0, 28.0, 30.0, 29.0, 27.0, 26.0, 26.0]));
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertJsonPath('cached', false);
});

test('the language is required and must be supported', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), [])->assertUnprocessable()->assertJsonValidationErrors('lang');
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'es'])->assertUnprocessable()->assertJsonValidationErrors('lang');
    Http::assertNothingSent();
});

test('a neighborhood without coordinates cannot be summarised', function () {
    fakeWeek();
    $quartier = summarisable(['latitude' => null, 'longitude' => null]);

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertUnprocessable()
        ->assertJsonPath('message', 'This neighborhood has no coordinates yet.');
    Http::assertNothingSent();
});

test('a weather outage gives a clear message and never calls the AI', function () {
    fakeWeek([]);
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500), 'generativelanguage.googleapis.com/*' => Http::response(weekReply())]);
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(503)
        ->assertJsonPath('message', 'The weather service is unavailable. Please try again later.');
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'generativelanguage'));
});

test('without a key the feature is off and the panel is hidden', function () {
    fakeWeek();
    config()->set('services.google.key', null);
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(503);
    $this->get(route('neighborhoods.show', $quartier))->assertOk()->assertDontSee('Your week at a glance');
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'generativelanguage'));
});

test('the neighborhood page shows the panel only when a forecast is available', function () {
    fakeWeek();
    $quartier = summarisable();

    $this->get(route('neighborhoods.show', $quartier))->assertOk()
        ->assertSee('Your week at a glance')->assertSee('Summarise my week')->assertSee('Tunisien')
        ->assertSee(route('neighborhoods.summary', $quartier), false)->assertSee('Generated by AI (Google Gemini)');

    fakeWeek([]);
    Http::swap(new Illuminate\Http\Client\Factory);
    Http::preventStrayRequests();
    Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
    Cache::flush();

    $this->get(route('neighborhoods.show', $quartier))->assertOk()->assertDontSee('Your week at a glance');
});

test('the week summary shares the daily budget with the alert explanation', function () {
    fakeWeek();
    config()->set('services.google.explain_daily_limit', 1);
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'fr'])->assertStatus(429)
        ->assertJsonPath('message', 'The daily explanation limit has been reached. Please try again tomorrow.');

    $alerte = AlerteMeteo::factory()->for($quartier)->create(['publiee' => true]);
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(429);
});

test('a busy provider is retried, a persistent outage and quota errors are explained', function () {
    $quartier = summarisable();

    fakeWeek(null, Http::sequence()->push([], 503)->push([], 503)->push(weekReply('Third time lucky.')));
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk()->assertJsonPath('headline', 'Third time lucky.');

    Cache::flush();
    fakeWeek(null, Http::response(['error' => ['message' => 'secret provider detail test-secret-key']], 429));
    $response = $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(429);
    expect($response->getContent())->not->toContain('secret provider detail')->not->toContain('test-secret-key');

    Cache::flush();
    fakeWeek(null, Http::response([], 503));
    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(503)
        ->assertJsonPath('message', 'The explanation is temporarily unavailable. Please try again later.');
});

test('malformed model output is rejected', function (array $body) {
    fakeWeek(null, Http::response($body));
    $quartier = summarisable();

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(502);
})->with([
    'not json' => [['candidates' => [['content' => ['parts' => [['text' => 'just words']]]]]]],
    'missing points' => [['candidates' => [['content' => ['parts' => [['text' => '{"headline":"x"}']]]]]]],
    'empty headline' => [['candidates' => [['content' => ['parts' => [['text' => '{"headline":" ","points":["a"]}']]]]]]],
    'no points' => [['candidates' => [['content' => ['parts' => [['text' => '{"headline":"x","points":[]}']]]]]]],
]);

test('markup is stripped, thinking parts are ignored and the list is capped at five', function () {
    $points = ['<b>One</b>', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven'];
    $body = weekReply('<script>alert(1)</script>Calm week.', $points);
    array_unshift($body['candidates'][0]['content']['parts'], ['thought' => true, 'text' => 'INTERNAL REASONING']);
    fakeWeek(null, Http::response($body));
    $quartier = summarisable();

    $response = $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();

    expect($response->json('headline'))->toBe('alert(1)Calm week.')
        ->and($response->json('points'))->toHaveCount(5)->and($response->json('points.0'))->toBe('One')
        ->and($response->getContent())->not->toContain('INTERNAL REASONING');
});

test('the endpoint is rate limited per client', function () {
    fakeWeek();
    $quartier = summarisable();

    foreach (range(1, 6) as $attempt) {
        $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertOk();
    }

    $this->postJson(route('neighborhoods.summary', $quartier), ['lang' => 'en'])->assertStatus(429);
});
