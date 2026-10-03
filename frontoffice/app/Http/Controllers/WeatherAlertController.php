<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherAlertController extends Controller
{
    public function index(Request $request): View
    {
        $quartierId = (string) $request->query('quartier_id', '');

        return view('pages.front.weather-alerts', [
            'alertes' => AlerteMeteo::with('quartier')->where('publiee', true)->when($quartierId !== '', fn ($query) => $query->where('quartier_id', $quartierId))->orderByDesc('date_debut')->get(),
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $quartierId,
        ]);
    }
}