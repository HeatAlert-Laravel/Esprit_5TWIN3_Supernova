<?php

namespace App\Support;

/**
 * Maps a forecast maximum temperature (°C) to a vigilance level.
 * Single source of truth, reused by the forecast screen and the automatic alert generation.
 *
 *   below 33 °C  -> vert
 *   33 to <37    -> jaune
 *   37 to <41    -> orange
 *   41 and above -> rouge
 */
class HeatLevel
{
    public const THRESHOLDS = ['rouge' => 41.0, 'orange' => 37.0, 'jaune' => 33.0];

    public static function forTemperature(float $maxTemperature): string
    {
        foreach (self::THRESHOLDS as $level => $threshold) {
            if ($maxTemperature >= $threshold) {
                return $level;
            }
        }

        return 'vert';
    }

    /** Orange and red levels are the ones that stress the power grid. */
    public static function risksOutage(string $level): bool
    {
        return in_array($level, ['orange', 'rouge'], true);
    }
}
