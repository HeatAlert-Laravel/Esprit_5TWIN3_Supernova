<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The module's single door to Google Gemini text generation (own GOOGLE_API_KEY / GOOGLE_EXPLAIN_MODEL).
 * Shared by "Explain this alert" and the neighborhood week summary: same key, same retries, same daily budget.
 */
class GeminiTextClient
{
    public const LANGUAGES = [
        'en' => 'English',
        'fr' => 'Français',
        'ar' => 'العربية',
        'tn' => 'Tunisien',
    ];

    private const LANGUAGE_RULES = [
        'en' => 'Write in plain, simple English.',
        'fr' => 'Écris en français simple et clair.',
        'ar' => 'Write in clear, simple Modern Standard Arabic, in Arabic script.',
        'tn' => 'Write in Tunisian Arabic (Derja, الدارجة التونسية), in Arabic script, the way a Tunisian neighbor would speak: natural everyday Tunisian words and expressions (for example برشا, توا, لازم, ياسر, باش), short simple sentences, and not formal Modern Standard Arabic. Style example only, do not copy it: "اليوم الجو باش يسخن برشا، خوذ احتياطك."',
    ];

    /** One shared daily budget for every AI feature of the module. */
    private const DAILY_BUCKET = 'alert-explainer:daily';

    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public static function isConfigured(): bool
    {
        $model = config('services.google.explain_model');

        return filled(config('services.google.key'))
            && is_string($model) && preg_match('/\Agemini-[a-z0-9][a-z0-9.\-]{0,80}\z/', $model) === 1;
    }

    public static function languageRule(string $lang): string
    {
        return self::LANGUAGE_RULES[$lang];
    }

    /** Counts one upstream generation against the daily budget. Call it right before {@see generateJson()}. */
    public function reserveRequest(): void
    {
        $budgetLock = Cache::lock('alert-explainer:budget-lock', 5);
        abort_unless($budgetLock->get(), 429, __('The assistant is busy. Please try again shortly.'));

        try {
            $limit = max(0, (int) config('services.google.explain_daily_limit'));
            abort_if($limit === 0 || RateLimiter::tooManyAttempts(self::DAILY_BUCKET, $limit), 429,
                __('The daily explanation limit has been reached. Please try again tomorrow.'));
            RateLimiter::hit(self::DAILY_BUCKET, 86400);
        } finally {
            $budgetLock->release();
        }
    }

    /**
     * Sends the facts to Gemini and returns the decoded JSON object it answers with.
     *
     * @param  array<string, mixed>  $facts  data the model may rely on (never instructions)
     * @param  array<string, mixed>  $schema  Gemini response schema describing the JSON object
     * @return array<string, mixed>
     */
    public function generateJson(string $system, array $facts, array $schema): array
    {
        abort_unless(self::isConfigured(), 503, __('The explanation is temporarily unavailable. Please try again later.'));

        $model = config('services.google.explain_model');

        // Gemini answers 503 "high demand" in intermittent spikes: retry a couple of times before giving up.
        // The extra time limit matters on Windows, where PHP counts wall-clock time against max_execution_time.
        @set_time_limit(120);

        try {
            $response = Http::withHeaders(['x-goog-api-key' => config('services.google.key')])
                ->acceptJson()->connectTimeout(5)->timeout(25)
                ->retry(3, 1500, fn ($exception) => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError()), throw: false)
                ->post(self::ENDPOINT.$model.':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => json_encode($facts, JSON_UNESCAPED_UNICODE)]]]],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 4096,
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                    ],
                ]);
        } catch (ConnectionException) {
            abort(503, __('The explanation service could not be reached. Please try again.'));
        }

        // Never expose the provider response, the request headers or the API key to readers.
        if (! $response->successful()) {
            Log::warning('gemini text: provider error', ['status' => $response->status(), 'model' => $model]);
        }
        abort_if($response->status() === 429, 429, __('The explanation service is busy or its quota is used up. Please try again later.'));
        abort_unless($response->successful(), 503, __('The explanation is temporarily unavailable. Please try again later.'));

        $text = collect($response->json('candidates.0.content.parts') ?? [])
            ->reject(fn ($part) => ! empty($part['thought']))
            ->pluck('text')->filter(fn ($value) => is_string($value))->implode('');
        $json = json_decode($text, true);

        abort_unless(is_array($json), 502, __('The explanation could not be produced. Please try again.'));

        return $json;
    }
}
