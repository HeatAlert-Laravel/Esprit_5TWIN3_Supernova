<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class HeatDistressAssistant
{
    public function __construct(private HeatDistressAi $ai, private CoolingLocationSearch $locations, private CoolingPointFinder $points) {}

    public function answer(array $data): array
    {
        $language = $data['language'];
        if (! empty($data['location_token'])) {
            try {
                $context = json_decode(Crypt::decryptString($data['location_token']), true);
            } catch (DecryptException) {
                abort(422, trans('heat_assistant.expired', [], $language));
            }
            abort_unless(is_array($context) && ($context['expires'] ?? 0) >= now()->timestamp,
                422, trans('heat_assistant.expired', [], $language));

            return $this->recommend($context['origin'], $context['accessible'], $context['language']);
        }

        $message = trim($data['message']);
        // Critical wording takes priority even when the provider/key is unavailable.
        if (preg_match('/confus|inconscient|perte de connaissance|ne (?:peux|peut) (?:plus|pas) (?:marcher|respirer)|can.?t (?:walk|breathe)|cannot (?:walk|breathe)|unconscious|seizure|فقد(?:ت|ان)? الوعي|فاقد الوعي|مغمي|مغمى|مش قادر (?:نمشي|نتنفس)|ما نجمش (?:نمشي|نتنفس)|ضيق (?:في )?التنفس|تشنج/iu', $message)) {
            return $this->reply('emergency', $language, ['emergency_first', 'emergency_second']);
        }
        $intent = $this->ai->interpret($message, $language);
        if ($intent['emergency']) {
            return $this->reply('emergency', $language, ['emergency_first', 'emergency_second']);
        }
        $accessible = ($data['accessible'] ?? false) || $intent['needs_accessible'];
        if (isset($data['latitude'], $data['longitude'])) {
            return $this->recommend(['latitude' => (float) $data['latitude'], 'longitude' => (float) $data['longitude'],
                'label' => trans('heat_assistant.gps_origin', [], $language)], $accessible, $language);
        }
        $choices = trim($intent['location']) === '' ? [] : $this->locations->search($intent['location'], $language);
        if ($choices === []) {
            return $this->reply('needs_location', $language, ['rest', 'need_location']);
        }

        $result = $this->reply('confirm_location', $language, ['rest', 'confirm']);
        $result['choices'] = array_map(fn ($origin) => ['label' => $origin['label'], 'token' => Crypt::encryptString(json_encode([
            'origin' => $origin, 'accessible' => (bool) $accessible, 'language' => $language, 'expires' => now()->addMinutes(5)->timestamp,
        ]))], $choices);

        return $result;
    }

    private function recommend(array $origin, bool $accessible, string $language): array
    {
        $point = $this->points->nearest($origin['latitude'], $origin['longitude'], $accessible);
        if ($point === null) {
            return $this->reply('no_match', $language, ['rest', 'no_match']);
        }
        // All factual instruction slots come from the current database, never the LLM.
        $result = $this->reply('found', $language, ['rest', 'found'], [
            'name' => $this->spokenLabel($point['name']), 'address' => $this->spokenLabel($point['address']),
            'distance' => (string) max(10, (int) (round($point['distance_km'] * 100) * 10)),
        ]);
        $result['point'] = $point;
        $result['origin'] = $origin['label'];
        $result['origin_coordinates'] = ['latitude' => (float) $origin['latitude'], 'longitude' => (float) $origin['longitude']];
        $result['directions_url'] = 'https://www.google.com/maps/dir/?'.http_build_query([
            'api' => 1, 'destination' => $point['latitude'].','.$point['longitude'], 'travelmode' => 'walking',
        ]);

        return $result;
    }

    private function reply(string $status, string $language, array $keys, array $replace = []): array
    {
        return ['status' => $status, 'language' => $language,
            'text' => implode(' ', array_map(fn ($key) => trans('heat_assistant.'.$key, $replace, $language), $keys)),
            'point' => null, 'choices' => []];
    }

    private function spokenLabel(string $label): string
    {
        return trim(preg_replace('/[.!?؟\r\n]+/u', ' ', $label));
    }
}
