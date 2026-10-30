<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mistral enrichment for the Module 5 household risk advisor.
 *
 * The local score/level computed by HouseholdRiskService stays authoritative: this client only
 * asks Mistral to explain the already-computed assessment and to suggest preparation actions.
 * Every failure (missing key, 401, 429, 5xx, timeout, invalid JSON, unexpected model output)
 * returns null so the controller can display the deterministic local fallback instead of a 500.
 */
class MistralHouseholdAdvisor
{
    private const ENDPOINT = 'https://api.mistral.ai/v1/chat/completions';

    /** Only a short, opaque model identifier is accepted from configuration. */
    private const MODEL_PATTERN = '/\A[a-zA-Z0-9][a-zA-Z0-9._\/-]{0,100}\z/';

    /** Maximum characters accepted in each returned field (server-side validation). */
    private const MAX_SUMMARY = 1200;

    private const MAX_FACTOR = 300;

    private const MAX_ACTION = 300;

    private const MAX_FACTORS = 5;

    private const MAX_ACTIONS = 5;

    /**
     * Ask Mistral to explain the local assessment.
     *
     * @param  array<string, mixed>  $assessment  Anonymised payload built by HouseholdRiskService.
     * @return array{summary: string, risk_factors: list<string>, priority_actions: list<array{priority: int, action: string}>}|null
     */
    public function advise(array $assessment): ?array
    {
        $key = config('services.mistral.key');
        $model = config('services.mistral.model');

        if (! is_string($key) || trim($key) === '' || ! is_string($model) || ! preg_match(self::MODEL_PATTERN, $model)) {
            return null;
        }

        $started = microtime(true);
        $response = $this->send($key, $model, $assessment, $started);

        if ($response === null) {
            return null;
        }

        $status = $response->status();
        $duration = (int) round((microtime(true) - $started) * 1000);

        if (! $response->successful()) {
            $this->logFailure($this->reasonForStatus($status), $status, $model, $duration);

            return null;
        }

        if ($response->json('choices.0.finish_reason') !== 'stop') {
            $this->logFailure('incomplete_output', $status, $model, $duration);

            return null;
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($this->stripCodeFence($content), true) : null;
        $result = $this->validate($decoded);

        if ($result === null) {
            $this->logFailure('invalid_output', $status, $model, $duration);

            return null;
        }

        Log::info('Household risk AI analysis completed', [
            'status' => $status,
            'model' => $model,
            'duration_ms' => $duration,
        ]);

        return $result;
    }

    /**
     * One attempt plus one retry only for network/connection failures (never for 429/5xx, to protect the quota).
     */
    private function send(string $key, string $model, array $assessment, float $started): ?Response
    {
        $timeout = max(2, min(30, (int) config('services.mistral.timeout', 15)));
        $connectTimeout = max(2, min(10, (int) config('services.mistral.connect_timeout', 5)));

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                return Http::withToken($key)
                    ->acceptJson()
                    ->asJson()
                    ->connectTimeout($connectTimeout)
                    ->timeout($timeout)
                    ->post(self::ENDPOINT, $this->payload($model, $assessment));
            } catch (ConnectionException) {
                if ($attempt === 2) {
                    $this->logFailure(
                        'connection_error',
                        null,
                        $model,
                        (int) round((microtime(true) - $started) * 1000),
                    );

                    return null;
                }
            } catch (Throwable) {
                $this->logFailure(
                    'request_error',
                    null,
                    $model,
                    (int) round((microtime(true) - $started) * 1000),
                );

                return null;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function payload(string $model, array $assessment): array
    {
        return [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user', 'content' => json_encode($assessment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
            'temperature' => 0.2,
            'max_tokens' => 900,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'household_risk_advice',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'summary' => ['type' => 'string', 'maxLength' => self::MAX_SUMMARY],
                            'risk_factors' => [
                                'type' => 'array',
                                'maxItems' => self::MAX_FACTORS,
                                'items' => ['type' => 'string', 'maxLength' => self::MAX_FACTOR],
                            ],
                            'priority_actions' => [
                                'type' => 'array',
                                'maxItems' => self::MAX_ACTIONS,
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'priority' => ['type' => 'integer', 'minimum' => 1, 'maximum' => self::MAX_ACTIONS],
                                        'action' => ['type' => 'string', 'maxLength' => self::MAX_ACTION],
                                    ],
                                    'required' => ['priority', 'action'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['summary', 'risk_factors', 'priority_actions'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return implode(' ', [
            'Tu es un conseiller préventif pour HeatAlert, une application d’aide aux foyers pendant les fortes chaleurs.',
            'Tu reçois un score et un niveau d’exposition déjà calculés localement par l’application, les facteurs détectés,',
            'et des données anonymisées du foyer et de ses équipements sensibles.',
            'Règles absolues :',
            '1. Utilise uniquement les informations fournies ; n’invente jamais un équipement, une caractéristique, une autonomie,',
            'un chiffre ou une donnée absente.',
            '2. Ne modifie jamais le score ni le niveau : ils restent ceux de l’application.',
            '3. Ne pose aucun diagnostic médical, ne prédit aucune maladie, accident ou décès, et ne remplace jamais un médecin,',
            'les autorités ou les professionnels compétents.',
            '4. N’affirme jamais qu’un foyer est garanti en sécurité.',
            '5. Donne des conseils courts, concrets et actionnables, en privilégiant la sécurité des personnes fragiles.',
            '6. Réponds uniquement en français, sans donnée personnelle (nom, adresse, téléphone, e-mail).',
            '7. N’utilise jamais de mise en forme Markdown : pas de **, de ##, de listes à puces ni de symboles * _ # ;',
            'texte brut uniquement, en phrases courtes.',
            '8. Réponds uniquement avec un objet JSON strict contenant : summary (deux à quatre phrases courtes de synthèse),',
            'risk_factors (au maximum 5 explications courtes des facteurs déjà fournis),',
            'priority_actions (au maximum 5 objets avec priority entier croissant à partir de 1 et action courte).',
            'N’expose pas ton raisonnement interne et n’ajoute aucun autre champ.',
        ]);
    }

    /** @return array{summary: string, risk_factors: list<string>, priority_actions: list<array{priority: int, action: string}>}|null */
    private function validate(mixed $decoded): ?array
    {
        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        $summary = $this->cleanText($decoded['summary'] ?? null, self::MAX_SUMMARY);
        if ($summary === null) {
            return null;
        }

        $rawFactors = $decoded['risk_factors'] ?? null;
        if (! is_array($rawFactors) || ! array_is_list($rawFactors) || count($rawFactors) > self::MAX_FACTORS) {
            return null;
        }

        $factors = [];
        foreach ($rawFactors as $factor) {
            $text = $this->cleanText($factor, self::MAX_FACTOR);
            if ($text === null) {
                return null;
            }
            $factors[] = $text;
        }

        $rawActions = $decoded['priority_actions'] ?? null;
        if (! is_array($rawActions) || ! array_is_list($rawActions) || $rawActions === [] || count($rawActions) > self::MAX_ACTIONS) {
            return null;
        }

        $actions = [];
        foreach ($rawActions as $entry) {
            if (! is_array($entry) || array_is_list($entry)) {
                return null;
            }
            $priority = $entry['priority'] ?? null;
            if (! is_int($priority) && ! (is_string($priority) && ctype_digit($priority))) {
                return null;
            }
            $priority = (int) $priority;
            if ($priority < 1 || $priority > self::MAX_ACTIONS) {
                return null;
            }
            $action = $this->cleanText($entry['action'] ?? null, self::MAX_ACTION);
            if ($action === null) {
                return null;
            }
            $actions[] = ['priority' => $priority, 'action' => $action];
        }

        usort($actions, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return ['summary' => $summary, 'risk_factors' => $factors, 'priority_actions' => $actions];
    }

    /** Trim, collapse whitespace, remove markup and markdown markers; reject empty/oversized/invalid UTF-8 text. */
    private function cleanText(mixed $value, int $max): ?string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return null;
        }

        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
        // Light server-side normalization: drop emphasis markers if the model returns markdown anyway.
        $text = preg_replace('/[*_~`]{1,3}/u', '', $text) ?? $text;
        $text = preg_replace('/#{1,6}\s*/u', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '' || mb_strlen($text) > $max) {
            return null;
        }

        return $text;
    }

    private function stripCodeFence(string $content): string
    {
        $content = trim($content);

        if (preg_match('/\A```(?:json)?\s*(.*?)\s*```\z/s', $content, $matches)) {
            return $matches[1];
        }

        return $content;
    }

    private function reasonForStatus(int $status): string
    {
        return match (true) {
            $status === 401 || $status === 403 => 'unauthorized',
            $status === 429 => 'rate_limited',
            $status >= 500 => 'provider_error',
            default => 'http_error',
        };
    }

    /** Never log the API key, the prompt, the payload or the raw provider response. */
    private function logFailure(string $reason, ?int $status, string $model, int $duration): void
    {
        Log::warning('Household risk AI unavailable', [
            'reason' => $reason,
            'status' => $status,
            'model' => $model,
            'duration_ms' => $duration,
        ]);
    }
}
