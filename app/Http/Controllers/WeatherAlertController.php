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
        $search = trim((string) $request->query('q', ''));
        $niveau = in_array($request->query('niveau'), AlerteMeteo::LEVELS, true) ? $request->query('niveau') : '';

        // Neighborhood and text filters apply to the list and to the counters; the level filter only to the list.
        $base = AlerteMeteo::query()->published()
            ->when($quartierId !== '', fn ($query) => $query->where('quartier_id', $quartierId))
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('titre', 'like', "%{$search}%")
                ->orWhereHas('quartier', fn ($quartierQuery) => $quartierQuery
                    ->where('nom', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%")
                    ->orWhere('gouvernorat', 'like', "%{$search}%"))));

        $activeByLevel = (clone $base)->current()->selectRaw('niveau, count(*) as total')->groupBy('niveau')->pluck('total', 'niveau');

        return view('alertes-meteo.resident-index', [
            'alertes' => (clone $base)->with('quartier')
                ->when($niveau !== '', fn ($query) => $query->where('niveau', $niveau))
                ->orderByDesc('date_debut')->get(),
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $quartierId,
            'search' => $search,
            'niveau' => $niveau,
            'activeByLevel' => collect(AlerteMeteo::LEVELS)->mapWithKeys(fn ($level) => [$level => (int) ($activeByLevel[$level] ?? 0)])->reverse(),
        ]);
    }
}
