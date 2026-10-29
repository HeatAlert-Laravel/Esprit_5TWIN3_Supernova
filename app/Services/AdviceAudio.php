<?php

namespace App\Services;

use App\Models\Conseil;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdviceAudio
{
    public const VOICES = ['female' => 'Kore', 'male' => 'Charon'];

    public function text(Conseil $article): string
    {
        return $article->titre."\n\n".$article->resume."\n\n".$article->contenu;
    }

    public function version(Conseil $article, string $voice): string
    {
        abort_unless(isset(self::VOICES[$voice]), 404);

        return hash('sha256', json_encode([1, config('services.gemini.tts_model'), $voice, $this->text($article)]));
    }

    public function path(Conseil $article, string $voice): string
    {
        return 'advice-audio/'.$article->id.'/'.$this->version($article, $voice).'.wav';
    }

    public function generate(Conseil $article, string $voice): void
    {
        $path = $this->path($article, $voice);
        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return;
        }
        abort_unless(filled(config('services.gemini.key')), 503, __('Article audio is not configured yet.'));
        $lock = Cache::lock('advice-audio:'.$this->version($article, $voice), 150);
        abort_unless($lock->get(), 409, __('This recording is already being prepared. Please try again shortly.'));
        try {
            if ($disk->exists($path)) {
                return;
            }
            $limit = max(0, (int) config('services.gemini.tts_daily_limit'));
            abort_if($limit === 0 || RateLimiter::tooManyAttempts('advice-audio:daily', $limit), 429,
                __('The daily audio generation limit has been reached. Please try again tomorrow.'));
            RateLimiter::hit('advice-audio:daily', 86400);
            $model = config('services.gemini.tts_model');
            abort_unless(is_string($model) && preg_match('/\Agemini-[a-z0-9.-]+tts(?:-preview)?\z/', $model), 503,
                __('Article audio is not configured yet.'));
            try {
                $response = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
                    ->acceptJson()->connectTimeout(10)->timeout(120)
                    ->post('https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent', [
                        'contents' => [['role' => 'user', 'parts' => [[
                            'text' => $this->text($article),
                            'speech_metadata' => ['style' => 'Warm, clear, calm narration. Natural conversational pace.'],
                        ]]]],
                        'generationConfig' => [
                            'responseModalities' => ['AUDIO'],
                            'responseFormat' => ['audio' => ['mimeType' => 'AUDIO_WAV']],
                            'speechConfig' => ['voiceConfig' => ['voice' => self::VOICES[$voice]]],
                        ],
                    ]);
            } catch (ConnectionException) {
                abort(503, __('The audio service could not be reached. Please try again.'));
            }
            // Never expose the provider response, request headers or API key to readers.
            abort_if($response->status() === 429, 429,
                __('The voice service is busy or its free quota is exhausted. Please try again later.'));
            abort_unless($response->successful(), 503, __('Audio is temporarily unavailable. Please try again later.'));
            $candidate = $response->json('candidates.0', []);
            abort_unless(($candidate['finishReason'] ?? '') === 'STOP', 503,
                __('The recording could not be completed. Please try again later.'));
            $audio = collect($candidate['content']['parts'] ?? [])
                ->first(fn ($part) => isset($part['inlineData']['data']))['inlineData'] ?? [];
            $bytes = base64_decode($audio['data'] ?? '', true);
            abort_unless(is_string($bytes) && strlen($bytes) > 44 && strlen($bytes) < 60000000
                && str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WAVE', 503,
                __('The voice service returned an unreadable recording. Please try again.'));
            $temporary = $path.'.'.Str::uuid().'.tmp';
            try {
                abort_unless($disk->put($temporary, $bytes) && $disk->move($temporary, $path), 503,
                    __('The recording could not be saved. Please try again.'));
            } finally {
                $disk->delete($temporary);
            }
        } finally {
            $lock->release();
        }
    }
}
