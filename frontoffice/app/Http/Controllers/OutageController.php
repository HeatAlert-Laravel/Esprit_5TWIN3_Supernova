<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignalementRequest;
use App\Models\Coupure;
use App\Models\Quartier;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OutageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['quartier_id' => ['nullable', 'integer', 'exists:quartiers,id']]);

        return view('pages.front.outages', [
            'coupures' => Coupure::with('quartier')->withCount('signalements')->where('statut', 'active')
                ->when($filters['quartier_id'] ?? null, fn ($query, $id) => $query->where('quartier_id', $id))
                ->latest('date_debut')->paginate(12)->withQueryString(),
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $filters['quartier_id'] ?? '',
        ]);
    }

    public function create(): View
    {
        return view('pages.front.report-outage', [
            'coupures' => Coupure::with('quartier')->where('statut', 'active')->latest('date_debut')->get(),
        ]);
    }

    public function store(SignalementRequest $request): RedirectResponse
    {
        Signalement::create($request->safe()->only(['coupure_id', 'adresse', 'description'])
            + ['user_id' => auth()->id(), 'statut' => 'en attente']);

        return redirect()->route('outages')->with('status', __('Report submitted.'));
    }
}
