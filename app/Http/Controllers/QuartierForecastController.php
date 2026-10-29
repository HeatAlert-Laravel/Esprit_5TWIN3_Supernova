<?php

namespace App\Http\Controllers;

use App\Models\Quartier;
use App\Services\OpenMeteoForecast;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class QuartierForecastController extends Controller
{
    /** Live 7-day forecast and suggested vigilance level, used to pre-fill a weather alert. */
    public function show(Quartier $quartier, OpenMeteoForecast $forecast): JsonResponse
    {
        try {
            $days = $forecast->forQuartier($quartier);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], $quartier->hasCoordinates() ? 503 : 422);
        }

        return response()->json([
            'quartier' => $quartier->nom,
            'source' => 'Open-Meteo',
            'days' => $days,
        ]);
    }
}
