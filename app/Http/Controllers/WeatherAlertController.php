<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use App\Services\NeighborhoodResources;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherAlertController extends Controller
{
    public function index(Request $request): View
    {
        $quartierId = (string) $request->query('quartier_id', '');
        $search = trim((string) $request->query('q', ''));
        $niveau = in_array($request->query('niveau'), AlerteMeteo::LEVELS, true) ? $request->query('niveau') : '';
        $periode = in_array($request->query('periode'), ['current', 'upcoming', 'past'], true) ? $request->query('periode') : '';

        // Neighborhood and text filters apply to the list, the map and the counters; the level filter only to the list and the map.
        $base = AlerteMeteo::query()->published()
            ->when($quartierId !== '', fn ($query) => $query->where('quartier_id', $quartierId))
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('titre', 'like', "%{$search}%")
                ->orWhereHas('quartier', fn ($quartierQuery) => $quartierQuery
                    ->where('nom', 'like', "%{$search}%")
                    ->orWhere('ville', 'like', "%{$search}%")
                    ->orWhere('gouvernorat', 'like', "%{$search}%"))));

        // Alerts active today, most severe first: they drive the counters, the hero summary and the map pins.
        $currentAlerts = (clone $base)->current()->with('quartier')->mostSevereFirst()->orderByDesc('temperature_max')->get();
        $activeByLevel = $currentAlerts->countBy('niveau');
        $currentByQuartier = $currentAlerts->groupBy('quartier_id');

        $mapQuartiers = Quartier::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($quartierId !== '', fn ($query) => $query->whereKey($quartierId))
            ->when($search !== '', fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('nom', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%")
                ->orWhere('gouvernorat', 'like', "%{$search}%")
                ->orWhereHas('alerteMeteos', fn ($alertQuery) => $alertQuery->published()->where('titre', 'like', "%{$search}%"))))
            ->orderBy('ville')->orderBy('nom')->get()
            ->map(function (Quartier $quartier) use ($currentByQuartier) {
                $alerts = $currentByQuartier->get($quartier->id);
                $top = $alerts?->first();

                return [
                    'id' => $quartier->id,
                    'nom' => $quartier->nom,
                    'ville' => $quartier->ville,
                    'lat' => $quartier->latitude,
                    'lng' => $quartier->longitude,
                    'niveau' => $top?->niveau ?? 'vert',
                    'label' => AlerteMeteo::labelFor($top?->niveau ?? 'vert'),
                    'active' => $alerts?->count() ?? 0,
                    'temperature' => $top?->temperature_max,
                    'title' => $top?->titre,
                    'outage' => (bool) $alerts?->contains('risque_coupure', true),
                    'url' => route('neighborhoods.show', $quartier),
                ];
            })
            ->when($niveau !== '', fn ($rows) => $rows->filter(fn (array $row) => $row['niveau'] === $niveau))
            ->values();

        // The list adds the level filter and the period tabs; the tab counts follow every other filter.
        $listBase = (clone $base)->when($niveau !== '', fn ($query) => $query->where('niveau', $niveau));
        $today = today()->toDateString();
        $periodCounts = [
            '' => (clone $listBase)->count(),
            'current' => (clone $listBase)->current()->count(),
            'upcoming' => (clone $listBase)->whereDate('date_debut', '>', $today)->count(),
            'past' => (clone $listBase)->whereDate('date_fin', '<', $today)->count(),
        ];

        $alertes = (clone $listBase)->with('quartier')
            ->when($periode === 'current', fn ($query) => $query->current())
            ->when($periode === 'upcoming', fn ($query) => $query->whereDate('date_debut', '>', $today))
            ->when($periode === 'past', fn ($query) => $query->whereDate('date_fin', '<', $today))
            // Active today first, then upcoming, then ended; the most severe and the most recent first inside each group.
            ->orderByRaw('CASE WHEN DATE(date_debut) <= ? AND DATE(date_fin) >= ? THEN 0 WHEN DATE(date_debut) > ? THEN 1 ELSE 2 END', [$today, $today, $today])
            ->mostSevereFirst()
            ->orderByDesc('date_debut')
            ->paginate(9)->withQueryString();

        return view('alertes-meteo.resident-index', [
            'alertes' => $alertes,
            'periode' => $periode,
            'periodCounts' => $periodCounts,
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $quartierId,
            'search' => $search,
            'niveau' => $niveau,
            'activeByLevel' => collect(AlerteMeteo::LEVELS)->mapWithKeys(fn ($level) => [$level => (int) ($activeByLevel[$level] ?? 0)])->reverse(),
            'activeTotal' => $currentAlerts->count(),
            'topAlert' => $currentAlerts->first(),
            'mapQuartiers' => $mapQuartiers,
        ]);
    }

    /** Detail page of one published alert, with the advice, cooling points and outages around it. */
    public function show(AlerteMeteo $alerte, NeighborhoodResources $resources): View
    {
        abort_unless($alerte->publiee, 404);

        $alerte->load('quartier');
        $quartier = $alerte->quartier;

        return view('alertes-meteo.resident-show', [
            'alerte' => $alerte,
            'quartier' => $quartier,
            'advice' => $resources->advice($alerte->risque_coupure),
            'coolingPoints' => $resources->coolingPoints($quartier),
            'coolingPointsCount' => $resources->coolingPointsCount($quartier),
            'outages' => $resources->activeOutages($quartier),
            'outagesCount' => $resources->activeOutagesCount($quartier),
            'otherAlerts' => $quartier->alerteMeteos()->published()->whereKeyNot($alerte->id)
                ->where(fn ($query) => $query->current()->orWhereDate('date_debut', '>', today()->toDateString()))
                ->mostSevereFirst()->orderBy('date_debut')->limit(4)->get(),
        ]);
    }
}
