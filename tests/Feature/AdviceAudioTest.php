<?php

use App\Models\Conseil;
use App\Services\AdviceAudio;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

function narrationWav(): string
{
    return pack('a4Va4a4VvvVVvva4V', 'RIFF', 38, 'WAVE', 'fmt ', 16, 1, 1, 24000, 48000, 2, 16, 'data', 2)."\0\0";
}

function narrationResponse(): array
{
    return ['candidates' => [[
        'finishReason' => 'STOP',
        'content' => ['parts' => [['inlineData' => ['mimeType' => 'audio/wav', 'data' => base64_encode(narrationWav())]]]],
    ]]];
}

beforeEach(function () {
    $this->artisan('migrate', ['--force' => true]);
    Cache::flush();
    Storage::fake('local');
    Http::preventStrayRequests();
    config(['services.gemini.key' => 'test-secret', 'services.gemini.tts_daily_limit' => 30]);
    $this->article = Conseil::factory()->published()->create();
    $this->audio = app(AdviceAudio::class);
});

test('published articles offer a reader without making an API request or exposing credentials', function () {
    $this->get(route('advice.show', $this->article))->assertOk()
        ->assertSee('Listen to this article')->assertSee('Kore · Female')->assertSee('Charon · Male')
        ->assertSee('preload="none"', false)->assertDontSee('test-secret')->assertDontSee('GEMINI_API_KEY');
    $html = $this->get(route('advice.show', $this->article))->assertOk()->getContent();
    expect(strpos($html, 'data-advice-reader'))->toBeLessThan(strpos($html, 'class="ha-advice-summary"'));
    expect($html)->toContain('data-reader-play-icon')->toContain('data-reader-options')
        ->not->toContain('<audio controls')->not->toContain('ha-audio-reader__heading');
    $this->get(route('home'))->assertOk()->assertDontSee('data-advice-reader', false);
    $this->get(route('advice'))->assertOk()->assertDontSee('data-advice-reader', false);
    Http::assertNothingSent();
});

test('audio is generated once and served with seek support without spending more quota', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(narrationResponse())]);
    $route = route('advice.audio.store', [$this->article, 'female']);
    $url = $this->postJson($route)->assertOk()->json('url');
    $this->postJson($route)->assertOk()->assertJsonPath('url', $url);
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-secret')
        && $request['contents'][0]['parts'][0]['text'] === $this->audio->text($this->article)
        && $request['generationConfig']['speechConfig']['voiceConfig']['voice'] === 'Kore');
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'audio/wav');
    $this->get($url, ['Range' => 'bytes=0-11'])->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-11/46');
    Http::assertSentCount(1);
});

test('male and female recordings are separate and article edits invalidate old audio', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(narrationResponse())]);
    $female = $this->postJson(route('advice.audio.store', [$this->article, 'female']))->assertOk()->json('url');
    $male = $this->postJson(route('advice.audio.store', [$this->article, 'male']))->assertOk()->json('url');
    expect($male)->not->toBe($female);
    Http::assertSent(fn ($request) => $request['generationConfig']['speechConfig']['voiceConfig']['voice'] === 'Charon');
    $this->article->update(['titre' => 'Updated advice article']);
    $this->get($female)->assertNotFound();
    $this->get($male)->assertNotFound();
    $fresh = $this->postJson(route('advice.audio.store', [$this->article, 'female']))->assertOk()->json('url');
    expect($fresh)->not->toBe($female);
    Http::assertSentCount(3);
});

test('unpublished articles cannot generate or serve audio even if already cached', function () {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(narrationResponse())]);
    $url = $this->postJson(route('advice.audio.store', [$this->article, 'female']))->assertOk()->json('url');
    $this->article->update(['actif' => false]);
    $this->get($url)->assertNotFound();
    $this->postJson(route('advice.audio.store', [$this->article, 'female']))->assertNotFound();
    Http::assertSentCount(1);
});

test('audio reads never trigger generation and unknown voices are rejected', function () {
    $this->get(route('advice.audio.show', [
        $this->article, 'female', $this->audio->version($this->article, 'female'),
    ]))->assertNotFound();
    $this->postJson('/advice/'.$this->article->id.'/audio/unknown')->assertNotFound();
    Http::assertNothingSent();
});

test('daily budget blocks new generation but cached recordings remain playable', function () {
    config(['services.gemini.tts_daily_limit' => 1]);
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(narrationResponse())]);
    $route = route('advice.audio.store', [$this->article, 'female']);
    $url = $this->postJson($route)->assertOk()->json('url');
    $this->postJson(route('advice.audio.store', [$this->article, 'male']))->assertStatus(429);
    $this->postJson($route)->assertOk();
    $this->get($url)->assertOk();
    Http::assertSentCount(1);
});

test('provider failures are friendly and never expose API response details', function ($status, $expected) {
    Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'private upstream detail']], $status)]);
    $this->postJson(route('advice.audio.store', [$this->article, 'female']))
        ->assertStatus($expected)->assertDontSee('private upstream detail')->assertDontSee('test-secret');
    Storage::disk('local')->assertMissing($this->audio->path($this->article, 'female'));
})->with([[429, 429], [403, 503], [500, 503]]);

test('missing configuration connection errors incomplete and invalid audio never get cached', function ($failure) {
    if ($failure === 'configuration') {
        config(['services.gemini.key' => null]);
    } elseif ($failure === 'connection') {
        Http::fake(fn () => throw new ConnectionException('Private network details'));
    } else {
        $response = narrationResponse();
        if ($failure === 'truncated') {
            $response['candidates'][0]['finishReason'] = 'MAX_TOKENS';
        } else {
            $response['candidates'][0]['content']['parts'][0]['inlineData']['data'] = base64_encode('not audio');
        }
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($response)]);
    }
    $this->postJson(route('advice.audio.store', [$this->article, 'female']))
        ->assertStatus(503)->assertDontSee('Private network details')->assertDontSee('test-secret');
    Storage::disk('local')->assertMissing($this->audio->path($this->article, 'female'));
})->with(['configuration', 'connection', 'truncated', 'invalid audio']);
