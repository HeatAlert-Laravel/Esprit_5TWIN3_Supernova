<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointFraicheur;
use App\Models\Quartier;
use App\Models\TypePoint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointFraicheurController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $typeId = $request->query('type_id');
        $quartierId = $request->query('quartier_id');
        $status = $request->query('status');

        $query = PointFraicheur::query()->with(['typePoint', 'quartier']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('adresse', 'like', "%{$search}%");
            });
        }

        if ($typeId) {
            $query->where('type_point_id', $typeId);
        }

        if ($quartierId) {
            $query->where('quartier_id', $quartierId);
        }

        if ($status === 'active') {
            $query->where('actif', true);
        } elseif ($status === 'inactive') {
            $query->where('actif', false);
        }

        $points = $query->latest()->paginate(10)->withQueryString();

        return view('pages.admin.point-fraicheurs.index', [
            'points' => $points,
            'types' => TypePoint::orderBy('nom')->get(),
            'quartiers' => Quartier::orderBy('nom')->get(),
            'search' => $search,
            'selectedType' => $typeId,
            'selectedQuartier' => $quartierId,
            'selectedStatus' => $status,
            'totalPoints' => PointFraicheur::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.point-fraicheurs.create', [
            'types' => TypePoint::orderBy('nom')->get(),
            'quartiers' => Quartier::orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $point = PointFraicheur::create($this->validated($request));

        return redirect()->route('admin.point-fraicheurs.show', $point)->with('status', 'Cooling point created successfully.');
    }

    public function show(PointFraicheur $pointFraicheur): View
    {
        $pointFraicheur->load(['typePoint', 'quartier']);

        return view('pages.admin.point-fraicheurs.show', ['point' => $pointFraicheur]);
    }

    public function edit(PointFraicheur $pointFraicheur): View
    {
        return view('pages.admin.point-fraicheurs.edit', [
            'point' => $pointFraicheur,
            'types' => TypePoint::orderBy('nom')->get(),
            'quartiers' => Quartier::orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, PointFraicheur $pointFraicheur): RedirectResponse
    {
        $pointFraicheur->update($this->validated($request));

        return redirect()->route('admin.point-fraicheurs.show', $pointFraicheur)->with('status', 'Cooling point updated successfully.');
    }

    public function toggleStatus(PointFraicheur $pointFraicheur): RedirectResponse
    {
        $pointFraicheur->update(['actif' => ! $pointFraicheur->actif]);

        $status = $pointFraicheur->actif ? 'activated (visible to residents)' : 'deactivated (hidden)';

        return back()->with('status', "Cooling point is now {$status}.");
    }

    public function destroy(PointFraicheur $pointFraicheur): RedirectResponse
    {
        $pointFraicheur->delete();

        return redirect()->route('admin.point-fraicheurs.index')->with('status', 'Cooling point deleted successfully.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'type_point_id' => ['required', 'exists:type_points,id'],
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'adresse' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'horaires' => ['nullable', 'string', 'max:100'],
            'accessible_pmr' => ['sometimes', 'boolean'],
            'actif' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['accessible_pmr'] = $request->boolean('accessible_pmr');
        $data['actif'] = $request->boolean('actif');

        return $data;
    }
}
