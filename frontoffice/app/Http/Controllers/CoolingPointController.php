<?php

namespace App\Http\Controllers;

use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CoolingPointController extends Controller
{
    public function index(Request $request): View
    {
        $quartierId = (string) $request->query('quartier_id', '');
        $typeId = (string) $request->query('type_id', '');
        $search = trim((string) $request->query('q', ''));
        $quick = (string) $request->query('quick', 'all');
        $sort = (string) $request->query('sort', 'nearest');
        $pmr = $request->query('pmr') === '1';

        $query = PointFraicheur::query()
            ->with(['typePoint', 'quartier'])
            ->actifs(); // Only active cooling points for residents

        if ($quartierId !== '') {
            $query->where('quartier_id', $quartierId);
        }

        if ($typeId !== '') {
            $query->where('type_point_id', $typeId);
        }

        if ($pmr) {
            $query->where('accessible_pmr', true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('adresse', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('quartier', function ($qQ) use ($search) {
                      $qQ->where('nom', 'like', "%{$search}%")
                         ->orWhere('ville', 'like', "%{$search}%");
                  });
            });
        }

        if ($quick === 'water') {
            $query->whereHas('typePoint', function ($qT) {
                $qT->where('nom', 'like', '%fountain%')
                   ->orWhere('nom', 'like', '%pool%')
                   ->orWhere('nom', 'like', '%mister%');
            });
        } elseif ($quick === 'shade') {
            $query->whereHas('typePoint', function ($qT) {
                $qT->where('nom', 'like', '%park%');
            });
        } elseif ($quick === 'indoor') {
            $query->whereHas('typePoint', function ($qT) {
                $qT->where('nom', 'like', '%hall%')
                   ->orWhere('nom', 'like', '%library%');
            });
        }

        if ($sort === 'name') {
            $query->orderBy('nom', 'asc');
        } else {
            $query->orderBy('nom', 'asc');
        }

        $types = TypePoint::withCount(['pointFraicheurs' => fn ($q) => $q->where('actif', true)])->orderBy('nom')->get();
        $quartiers = Quartier::orderBy('ville')->orderBy('nom')->get();

        $allPoints = $query->get();

        if ($quick === 'open') {
            $allPoints = $allPoints->filter(function ($pt) {
                return $pt->getOpenStatus()['is_open'] === true;
            })->values();
        }

        $mapPoints = $allPoints->map(function ($p) {
            $st = $p->getOpenStatus();
            return [
                'id' => $p->id,
                'name' => $p->nom,
                'type' => $p->typePoint?->nom ?? 'Cooling zone',
                'icon' => $p->typePoint?->icone ?? 'snowflake',
                'address' => $p->adresse,
                'neighborhood' => $p->quartier ? ($p->quartier->nom . ', ' . $p->quartier->ville) : '',
                'lat' => (float) $p->latitude,
                'lng' => (float) $p->longitude,
                'hours' => $p->horaires ?? 'Not specified',
                'is_open' => $st['is_open'],
                'status_label' => $st['label'],
                'status_detail' => $st['detail'],
                'description' => $p->description ?? 'No extra description available.',
                'pmr' => (bool) $p->accessible_pmr,
            ];
        });

        return view('pages.front.cooling-points', [
            'points' => $allPoints,
            'mapPoints' => $mapPoints,
            'types' => $types,
            'quartiers' => $quartiers,
            'quartierId' => $quartierId,
            'typeId' => $typeId,
            'search' => $search,
            'quick' => $quick,
            'sort' => $sort,
            'pmr' => $pmr,
            'currentType' => $typeId !== '' ? $types->firstWhere('id', (int) $typeId) : null,
            'currentQuartier' => $quartierId !== '' ? $quartiers->firstWhere('id', (int) $quartierId) : null,
            'totalActive' => PointFraicheur::actifs()->count(),
            'totalPmr' => PointFraicheur::actifs()->where('accessible_pmr', true)->count(),
        ]);
    }
}
