<?php

namespace App\Services;

use App\Models\Quartier;
use App\Support\HeatLevel;
use App\Support\WeatherCode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Forecast for a neighborhood from Open-Meteo (free, no API key).
 * Results are cached per coordinate pair.
 */
class OpenMeteoForecast
{
    private const ENDPOINT = 'https://api.open-meteo.com/v1/forecast';

    /**
     * Daily forecast used to pre-fill a weather alert (cached 30 minutes).
     *
     * @return list<array{date: string, temperature_max: float, temperature_min: float, temperature_ressentie: float, humidite: int, indice_uv: int, vitesse_vent: float, niveau: string, risque_coupure: bool}>
     *
     * @throws RuntimeException when the neighborhood has no coordinates or the service is unavailable
     */
    public function forQuartier(Quartier $quartier, int $days = 7): array
    {
        $this->assertLocated($quartier);

        $days = max(1, min($days, 16));
        $key = 'open-meteo:'.hash('sha256', json_encode([round($quartier->latitude, 3), round($quartier->longitude, 3), $days]));

        return Cache::remember($key, now()->addMinutes(30), fn () => $this->fetch($quartier->latitude, $quartier->longitude, $days));
    }

    /**
     * Full "weather app" view of a neighborhood: current conditions, the next 24 hours and the next days (cached 15 minutes).
     * `current` is null and `hourly` empty when the service does not return them.
     *
     * @return array{current: array<string, mixed>|null, hourly: list<array<string, mixed>>, daily: list<array<string, mixed>>}
     *
     * @throws RuntimeException when the neighborhood has no coordinates or the service is unavailable
     */
    public function detailed(Quartier $quartier, int $days = 7): array
    {
        $this->assertLocated($quartier);

        $days = max(1, min($days, 16));
        $key = 'open-meteo-detailed:'.hash('sha256', json_encode([round($quartier->latitude, 3), round($quartier->longitude, 3), $days]));

        return Cache::remember($key, now()->addMinutes(15), fn () => $this->fetchDetailed($quartier->latitude, $quartier->longitude, $days));
    }

    private function assertLocated(Quartier $quartier): void
    {
        if (! $quartier->hasCoordinates()) {
            throw new RuntimeException(__('This neighborhood has no coordinates yet.'));
        }
    }

    /** @return array<string, mixed> */
    private function request(float $latitude, float $longitude, int $days, array $fields): array
    {
        try {
            $response = Http::acceptJson()->connectTimeout(5)->timeout(10)->get(self::ENDPOINT, [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => config('app.timezone') === 'UTC' ? 'Africa/Tunis' : config('app.timezone'),
                'forecast_days' => $days,
            ] + $fields);
        } catch (ConnectionException) {
            throw new RuntimeException(__('The weather service is unreachable. Please try again later.'));
        }

        $daily = $response->json('daily');

        if ($response->failed() || ! is_array($daily) || empty($daily['time'])) {
            throw new RuntimeException(__('The weather service is unavailable. Please try again later.'));
        }

        return $response->json();
    }

    private function fetch(float $latitude, float $longitude, int $days): array
    {
        $daily = $this->request($latitude, $longitude, $days, [
            'daily' => 'temperature_2m_max,temperature_2m_min,apparent_temperature_max,uv_index_max,wind_speed_10m_max,relative_humidity_2m_mean',
        ])['daily'];

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

    private function fetchDetailed(float $latitude, float $longitude, int $days): array
    {
        $body = $this->request($latitude, $longitude, $days, [
            'current' => 'temperature_2m,apparent_temperature,relative_humidity_2m,weather_code,wind_speed_10m,is_day,precipitation',
            'hourly' => 'temperature_2m,weather_code,precipitation_probability,is_day',
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,uv_index_max,wind_speed_10m_max,precipitation_probability_max,sunrise,sunset,relative_humidity_2m_mean',
        ]);

        return [
            'current' => $this->parseCurrent($body['current'] ?? null),
            'hourly' => $this->parseHourly($body['hourly'] ?? null, $body['current']['time'] ?? null),
            'daily' => $this->parseDaily($body['daily']),
        ];
    }

    private function parseCurrent(mixed $current): ?array
    {
        if (! is_array($current) || ! isset($current['temperature_2m'])) {
            return null;
        }

        $code = (int) ($current['weather_code'] ?? 0);
        $isDay = (bool) ($current['is_day'] ?? 1);

        return [
            'temperature' => round((float) $current['temperature_2m'], 1),
            'ressentie' => round((float) ($current['apparent_temperature'] ?? $current['temperature_2m']), 1),
            'humidite' => (int) round($current['relative_humidity_2m'] ?? 0),
            'vent' => round((float) ($current['wind_speed_10m'] ?? 0), 1),
            'precipitation' => round((float) ($current['precipitation'] ?? 0), 1),
            'code' => $code,
            'is_day' => $isDay,
            'condition' => WeatherCode::describe($code),
            'time' => $current['time'] ?? null,
        ];
    }

    /** The 24 hours starting from the current hour. */
    private function parseHourly(mixed $hourly, ?string $currentTime): array
    {
        if (! is_array($hourly) || empty($hourly['time'])) {
            return [];
        }

        $from = $currentTime !== null ? substr($currentTime, 0, 13).':00' : ($hourly['time'][0] ?? '');
        $rows = [];

        foreach ($hourly['time'] as $index => $time) {
            if ($time < $from || ! isset($hourly['temperature_2m'][$index])) {
                continue;
            }

            $code = (int) ($hourly['weather_code'][$index] ?? 0);
            $rows[] = [
                'time' => $time,
                'hour' => substr($time, 11, 5),
                'temperature' => (int) round($hourly['temperature_2m'][$index]),
                'code' => $code,
                'is_day' => (bool) ($hourly['is_day'][$index] ?? 1),
                'condition' => WeatherCode::describe($code),
                'pluie' => isset($hourly['precipitation_probability'][$index]) ? (int) $hourly['precipitation_probability'][$index] : null,
            ];

            if (count($rows) === 24) {
                break;
            }
        }

        return $rows;
    }

    private function parseDaily(array $daily): array
    {
        $rows = [];

        foreach ($daily['time'] as $index => $date) {
            $max = $daily['temperature_2m_max'][$index] ?? null;

            if ($max === null) {
                continue;
            }

            $level = HeatLevel::forTemperature((float) $max);
            $code = (int) ($daily['weather_code'][$index] ?? 0);

            $rows[] = [
                'date' => $date,
                'temperature_max' => round((float) $max, 1),
                'temperature_min' => round((float) ($daily['temperature_2m_min'][$index] ?? $max), 1),
                'temperature_ressentie' => round((float) ($daily['apparent_temperature_max'][$index] ?? $max), 1),
                'humidite' => (int) round($daily['relative_humidity_2m_mean'][$index] ?? 0),
                'indice_uv' => (int) round($daily['uv_index_max'][$index] ?? 0),
                'vitesse_vent' => round((float) ($daily['wind_speed_10m_max'][$index] ?? 0), 1),
                'pluie' => isset($daily['precipitation_probability_max'][$index]) ? (int) $daily['precipitation_probability_max'][$index] : null,
                'lever' => isset($daily['sunrise'][$index]) ? substr($daily['sunrise'][$index], 11, 5) : null,
                'coucher' => isset($daily['sunset'][$index]) ? substr($daily['sunset'][$index], 11, 5) : null,
                'code' => $code,
                'condition' => WeatherCode::describe($code),
                'niveau' => $level,
                'risque_coupure' => HeatLevel::risksOutage($level),
            ];
        }

        return $rows;
    }
}
