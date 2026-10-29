<?php

namespace App\Support;

/**
 * Translates a WMO weather code (used by Open-Meteo) into a short label and an icon kind
 * understood by the <x-ha.weather-icon> component.
 */
class WeatherCode
{
    /** @return array{label: string, kind: string} */
    public static function describe(int $code): array
    {
        return match (true) {
            $code === 0 => ['label' => 'Clear sky', 'kind' => 'clear'],
            $code === 1 => ['label' => 'Mostly clear', 'kind' => 'clear'],
            $code === 2 => ['label' => 'Partly cloudy', 'kind' => 'partly'],
            $code === 3 => ['label' => 'Overcast', 'kind' => 'cloudy'],
            in_array($code, [45, 48], true) => ['label' => 'Fog', 'kind' => 'fog'],
            in_array($code, [51, 53, 55, 56, 57], true) => ['label' => 'Drizzle', 'kind' => 'rain'],
            in_array($code, [61, 63, 65, 66, 67], true) => ['label' => 'Rain', 'kind' => 'rain'],
            in_array($code, [71, 73, 75, 77, 85, 86], true) => ['label' => 'Snow', 'kind' => 'snow'],
            in_array($code, [80, 81, 82], true) => ['label' => 'Rain showers', 'kind' => 'rain'],
            in_array($code, [95, 96, 99], true) => ['label' => 'Thunderstorm', 'kind' => 'thunder'],
            default => ['label' => 'Unknown', 'kind' => 'cloudy'],
        };
    }
}
