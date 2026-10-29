<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Services\NeighborhoodResources;
use App\Services\OpenMeteoForecast;
use Illuminate\View\View;
use RuntimeException;

class NeighborhoodController extends Controller
{
    /** Resident page of one neighborhood: current vigilance level, its alerts and the live forecast. */
    public function show(Quartier $quartier, OpenMeteoForecast $forecast, NeighborhoodResources $resources): View
    {
        $alertes = $quartier->alerteMeteos()->published()->mostSevereFirst()->orderByDesc('date_debut')->get();

        $current = $alertes->filter(fn (AlerteMeteo $alerte) => $alerte->temporalStatus() === 'current')->values();
        $upcoming = $alertes->filter(fn (AlerteMeteo $alerte) => $alerte->temporalStatus() === 'upcoming')->sortBy('date_debut')->values();
        $past = $alertes->filter(fn (AlerteMeteo $alerte) => $alerte->temporalStatus() === 'expired')->sortByDesc('date_fin')->take(5)->values();

        // The list is already ordered most severe first, so the first current alert sets the level.
        $level = $current->first()?->niveau ?? 'vert';

        $weather = null;
        $weatherError = null;

        if ($quartier->hasCoordinates()) {
            try {
                $weather = $forecast->detailed($quartier, 7);
            } catch (RuntimeException $exception) {
                $weatherError = $exception->getMessage();
            }
        }

        return view('quartiers.resident-show', [
            'quartier' => $quartier,
            'current' => $current,
            'upcoming' => $upcoming,
            'past' => $past,
            'level' => $level,
            'weather' => $weather,
            'weatherError' => $weatherError,
            'coolingPointsCount' => $resources->coolingPointsCount($quartier),
            'outagesCount' => $resources->activeOutagesCount($quartier),
        ]);
    }
}
