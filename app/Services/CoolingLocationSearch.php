<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CoolingLocationSearch
{
    public function search(string $location, string $language): array
    {
        $url = config('heat_assistant.geocoder_url');
        if (! filled($url)) {
            return [];
        }
        $key = 'heat-assistant:geocode:'.hash('sha256', $url.$location);
        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }
        // Shared across clients; never autocomplete or bulk-query the demo service.
        abort_unless(Cache::lock('heat-assistant:geocoder-second', 1)->get(), 429,
            trans('heat_assistant.limit', [], $language));
        try {
            $response = Http::acceptJson()->withUserAgent('HeatAlert-CoolingAssistant/1.0')
                ->connectTimeout(3)->timeout(8)->get($url, [
                    'q' => $location, 'countrycode' => 'TN', 'limit' => 3,
                ]);
        } catch (ConnectionException) {
            $this->unavailable($language);
        }
        if (! $response->successful()) {
            $this->unavailable($language);
        }
        $features = $response->json('features');
        if (! is_array($features)) {
            $this->unavailable($language);
        }
        $choices = [];
        foreach (array_slice($features, 0, 3) as $feature) {
            if (! is_array($feature) || ! is_array($feature['properties'] ?? null)
                || data_get($feature, 'geometry.type', 'Point') !== 'Point') {
                continue;
            }
            $coords = data_get($feature, 'geometry.coordinates');
            $properties = $feature['properties'] ?? [];
            if (! is_array($coords) || count($coords) !== 2 || ! is_numeric($coords[0]) || ! is_numeric($coords[1])
                || abs((float) $coords[0]) > 180 || abs((float) $coords[1]) > 90
                || strtoupper($properties['countrycode'] ?? '') !== 'TN') {
                continue;
            }
            $label = implode(', ', array_unique(array_filter(array_map(
                fn ($field) => is_string($properties[$field] ?? null) ? $properties[$field] : '',
                ['name', 'street', 'city', 'state', 'country']
            ))));
            if ($label !== '' && mb_strlen($label) <= 500) {
                $choices[] = ['label' => $label, 'latitude' => (float) $coords[1], 'longitude' => (float) $coords[0]];
            }
        }
        Cache::put($key, $choices, now()->addDay());

        return $choices;
    }

    private function unavailable(string $language): never
    {
        throw new HttpResponseException(response()->json([
            'code' => 'location_unavailable', 'message' => trans('heat_assistant.location_unavailable', [], $language),
        ], 503)->header('Cache-Control', 'no-store'));
    }
}
