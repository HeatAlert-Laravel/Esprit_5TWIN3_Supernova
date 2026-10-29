<?php

namespace App\Services;

use App\Models\Quartier;
use App\Support\HeatLevel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;


class OpenMeteoForecast
{
    private const ENDPOINT = 'https://api.open-meteo.com/v1/forecast';

    /**
     * @return list<array{date: string, temperature_max: float, temperature_min: float, temperature_ressentie: float, humidite: int, indice_uv: int, vitesse_vent: float, niveau: string, risque_coupure: bool}>
     *
     * @throws RuntimeException when the neighborhood has no coordinates or the service is unavailable
     */
    public function forQuartier(Quartier $quartier, int $days = 7): array
    {
        if (! $quartier->hasCoordinates()) {
            throw new RuntimeException(__('This neighborhood has no coordinates yet.'));
        }

        $days = max(1, min($days, 16));
        $key = 'open-meteo:'.hash('sha256', json_encode([round($quartier->latitude, 3), round($quartier->longitude, 3), $days]));

        return Cache::remember($key, now()->addMinutes(30), fn () => $this->fetch($quartier->latitude, $quartier->longitude, $days));
    }

    private function fetch(float $latitude, float $longitude, int $days): array
    {
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(10)->get(self::ENDPOINT, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'daily' => 'temperature_2m_max,temperature_2m_min,apparent_temperature_max,uv_index_max,wind_speed_10m_max,relative_humidity_2m_mean',
                'timezone' => config('app.timezone') === 'UTC' ? 'Africa/Tunis' : config('app.timezone'),
                'forecast_days' => $days,
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException(__('The weather service is unreachable. Please try again later.'));
        }

        $daily = $response->json('daily');

        if ($response->failed() || ! is_array($daily) || empty($daily['time'])) {
            throw new RuntimeException(__('The weather service is unavailable. Please try again later.'));
        }

        $forecast = [];

        foreach ($daily['time'] as $index => $date) {
            $max = $daily['temperature_2m_max'][$index] ?? null;

            if ($max === null) {
                continue;
            }

            $level = HeatLevel::forTemperature((float) $max);

            $forecast[] = [
                'date' => $date,
                'temperature_max' => round((float) $max, 1),
                'temperature_min' => round((float) ($daily['temperature_2m_min'][$index] ?? $max), 1),
                'temperature_ressentie' => round((float) ($daily['apparent_temperature_max'][$index] ?? $max), 1),
                'humidite' => (int) round($daily['relative_humidity_2m_mean'][$index] ?? 0),
                'indice_uv' => (int) round($daily['uv_index_max'][$index] ?? 0),
                'vitesse_vent' => round((float) ($daily['wind_speed_10m_max'][$index] ?? 0), 1),
                'niveau' => $level,
                'risque_coupure' => HeatLevel::risksOutage($level),
            ];
        }

        return $forecast;
    }
}
