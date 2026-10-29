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

function explainableAlert(array $attributes = []): AlerteMeteo
{
    $quartier = Quartier::factory()->create(['nom' => 'Carthage', 'ville' => 'Tunis']);

    return AlerteMeteo::factory()->for($quartier)->create($attributes + [
        'publiee' => true, 'niveau' => 'rouge', 'temperature_max' => 43, 'risque_coupure' => true,
        'date_debut' => now()->toDateString(), 'date_fin' => now()->addDay()->toDateString(),
    ]);
}

function geminiReply(string $summary = 'A red heat alert is active.', array $actions = ['Stay inside.', 'Drink water.'], array $extraParts = []): array
{
    return ['candidates' => [['content' => ['parts' => array_merge($extraParts, [['text' => json_encode(['summary' => $summary, 'actions' => $actions], JSON_UNESCAPED_UNICODE)]])]]]];
}

function fakeGemini(mixed $response = null): void
{
    Http::fake(['generativelanguage.googleapis.com/*' => $response ?? Http::response(geminiReply())]);
}

test('it returns a plain-language explanation of a published alert', function () {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk()
        ->assertJson(['summary' => 'A red heat alert is active.', 'actions' => ['Stay inside.', 'Drink water.'], 'lang' => 'en', 'cached' => false])
        ->assertHeader('Cache-Control');
});

test('the request uses the module key and model and sends only the alert facts', function () {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk();

    Http::assertSent(function (Request $request) {
        $body = $request->data();
        $facts = $body['contents'][0]['parts'][0]['text'];

        return str_contains($request->url(), '/models/gemini-3.5-flash:generateContent')
            && $request->header('x-goog-api-key') === ['test-secret-key']
            && str_contains($facts, 'Carthage') && str_contains($facts, '"max_temperature_c":43')
            && ! str_contains($facts, 'test-secret-key')
            && $body['generationConfig']['responseMimeType'] === 'application/json'
            && str_contains($body['systemInstruction']['parts'][0]['text'], 'Do not give personalised medical advice');
    });
});

test('each language is requested with its own instruction', function (string $lang, string $expected) {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => $lang])->assertOk()->assertJsonPath('lang', $lang);

    Http::assertSent(fn (Request $request) => str_contains($request->data()['systemInstruction']['parts'][0]['text'], $expected));
})->with([
    ['en', 'plain, simple English'],
    ['fr', 'en français simple'],
    ['ar', 'Modern Standard Arabic'],
    ['tn', 'Tunisian Arabic (Derja'],
]);

test('an explanation is cached per alert content and language', function () {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertJsonPath('cached', false);
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertJsonPath('cached', true);
    Http::assertSentCount(1);

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'fr'])->assertJsonPath('cached', false);
    Http::assertSentCount(2);
});

test('editing the alert invalidates its cached explanation', function () {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk();
    $alerte->update(['temperature_max' => 46]);
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertJsonPath('cached', false);

    Http::assertSentCount(2);
});

test('drafts cannot be explained', function () {
    fakeGemini();
    $draft = explainableAlert(['publiee' => false]);

    $this->postJson(route('weather-alerts.explain', $draft), ['lang' => 'en'])->assertNotFound();
    Http::assertNothingSent();
});

test('the language is required and must be supported', function () {
    fakeGemini();
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), [])->assertUnprocessable()->assertJsonValidationErrors('lang');
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'de'])->assertUnprocessable()->assertJsonValidationErrors('lang');
    Http::assertNothingSent();
});

test('without a key the feature is off, the panel is hidden and nothing is sent', function () {
    fakeGemini();
    config()->set('services.google.key', null);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(503);
    $this->get(route('weather-alerts.show', $alerte))->assertOk()->assertDontSee('Explain this alert');
    Http::assertNothingSent();
});

test('an invalid model name is refused before any request', function () {
    fakeGemini();
    config()->set('services.google.explain_model', 'gpt-4/../evil');
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(503);
    Http::assertNothingSent();
});

test('the detail page offers the explanation panel with the four languages and an AI notice', function () {
    $alerte = explainableAlert();

    $this->get(route('weather-alerts.show', $alerte))->assertOk()
        ->assertSee('Explain this alert')->assertSee('English')->assertSee('Français')->assertSee('العربية')->assertSee('Tunisien')
        ->assertSee('Generated by AI from the data on this page only')->assertSee(route('weather-alerts.explain', $alerte), false);
});

test('a busy provider is retried and the explanation still comes back', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()
        ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
        ->push(['error' => ['code' => 503, 'status' => 'UNAVAILABLE']], 503)
        ->push(geminiReply('It worked on the third try.'))]);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk()->assertJsonPath('summary', 'It worked on the third try.');
    Http::assertSentCount(3);
});

test('a provider that stays down gives a friendly message after three attempts', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'secret provider detail']], 503)]);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(503)
        ->assertJsonPath('message', 'The explanation is temporarily unavailable. Please try again later.');
    Http::assertSentCount(3);
});

test('a quota error is explained without retrying and never leaks provider details or the key', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'secret provider detail test-secret-key']], 429)]);
    $alerte = explainableAlert();

    $response = $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(429);

    expect($response->getContent())->not->toContain('secret provider detail')->not->toContain('test-secret-key');
    Http::assertSentCount(1);
});

test('a client error from the provider is not retried', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'API key not valid']], 400)]);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(503);
    Http::assertSentCount(1);
});

test('malformed or empty model output is rejected', function (array $body) {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response($body)]);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(502);
})->with([
    'not json' => [['candidates' => [['content' => ['parts' => [['text' => 'just words']]]]]]],
    'missing actions' => [['candidates' => [['content' => ['parts' => [['text' => '{"summary":"x"}']]]]]]],
    'empty summary' => [['candidates' => [['content' => ['parts' => [['text' => '{"summary":"  ","actions":["a"]}']]]]]]],
    'no candidates' => [['promptFeedback' => ['blockReason' => 'SAFETY']]],
]);

test('thinking parts are ignored, markup is stripped and the list is capped', function () {
    $actions = ['<b>One</b>', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight'];
    fakeGemini(Http::response(geminiReply('<script>alert(1)</script>Stay cool.', $actions, [['thought' => true, 'text' => 'INTERNAL REASONING']])));
    $alerte = explainableAlert();

    $response = $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk();

    expect($response->json('summary'))->toBe('alert(1)Stay cool.')
        ->and($response->json('actions'))->toHaveCount(6)->and($response->json('actions.0'))->toBe('One')
        ->and($response->getContent())->not->toContain('INTERNAL REASONING');
});

test('the daily budget stops new generations but not cached ones', function () {
    fakeGemini();
    config()->set('services.google.explain_daily_limit', 1);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk();
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'fr'])->assertStatus(429)
        ->assertJsonPath('message', 'The daily explanation limit has been reached. Please try again tomorrow.');
    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk()->assertJsonPath('cached', true);
    Http::assertSentCount(1);
});

test('a zero daily limit switches generation off', function () {
    fakeGemini();
    config()->set('services.google.explain_daily_limit', 0);
    $alerte = explainableAlert();

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(429);
    Http::assertNothingSent();
});

test('the endpoint is rate limited per client', function () {
    fakeGemini();
    $alerte = explainableAlert();

    foreach (range(1, 6) as $attempt) {
        $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertOk();
    }

    $this->postJson(route('weather-alerts.explain', $alerte), ['lang' => 'en'])->assertStatus(429);
});
