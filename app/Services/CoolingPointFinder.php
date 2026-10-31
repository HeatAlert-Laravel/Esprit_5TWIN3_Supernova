<?php

namespace App\Services;

use App\Models\PointFraicheur;
use Carbon\CarbonImmutable;

class CoolingPointFinder
{
    public function nearest(float $latitude, float $longitude, bool $accessible): ?array
    {
        $now = CarbonImmutable::now(config('heat_assistant.timezone'));
        $radius = max(0, (float) config('heat_assistant.radius_km'));

        return PointFraicheur::query()->actifs()
            ->where('eau_potable', true)->where('ombre', true)
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($accessible, fn ($query) => $query->where('accessible_pmr', true))
            ->get()->filter(fn ($point) => $this->isOpen($point->horaires, $now)
                && is_numeric($point->latitude) && is_numeric($point->longitude)
                && abs((float) $point->latitude) <= 90 && abs((float) $point->longitude) <= 180)
            ->map(function ($point) use ($latitude, $longitude) {
                $distance = $this->distance($latitude, $longitude, (float) $point->latitude, (float) $point->longitude);

                return ['id' => $point->id, 'name' => $point->nom, 'address' => $point->adresse,
                    'latitude' => (float) $point->latitude, 'longitude' => (float) $point->longitude,
                    'distance_km' => $distance, 'hours' => $point->horaires];
            })
            ->filter(fn ($point) => $point['distance_km'] <= $radius)
            ->sortBy([['distance_km', 'asc'], ['id', 'asc']])->first();
    }

    // Conservative daily-hours parser. Unknown or day-specific text is never "open now".
    public function isOpen(?string $hours, CarbonImmutable $now): bool
    {
        $hours = mb_strtolower(trim($hours ?? ''));
        if (in_array($hours, ['24/7', '24h/24', 'always open'], true)) {
            return true;
        }
        if (! preg_match('/\A([01]?\d|2[0-3]):([0-5]\d)\s*[-–]\s*([01]?\d|2[0-3]):([0-5]\d)\z/u', $hours, $m)) {
            return false;
        }
        $start = (int) $m[1] * 60 + (int) $m[2];
        $end = (int) $m[3] * 60 + (int) $m[4];
        $time = $now->hour * 60 + $now->minute;

        return $start < $end ? $time >= $start && $time < $end
            : ($start > $end && ($time >= $start || $time < $end));
    }

    private function distance(float $lat, float $lng, float $otherLat, float $otherLng): float
    {
        $a = sin(deg2rad($otherLat - $lat) / 2) ** 2
            + cos(deg2rad($lat)) * cos(deg2rad($otherLat)) * sin(deg2rad($otherLng - $lng) / 2) ** 2;

        return 6371 * 2 * asin(sqrt(min(1, max(0, $a))));
    }
}
