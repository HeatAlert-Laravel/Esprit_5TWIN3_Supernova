<?php

namespace App\Http\Controllers;

use App\Http\Requests\M2IndexRequest;
use App\Http\Requests\ResidentSignalementRequest;
use App\Models\Coupure;
use App\Models\Quartier;
use App\Models\Signalement;
use App\Support\M2Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OutageController extends Controller
{
    public function index(M2IndexRequest $request): View
    {
        return view('coupures.resident-index', [
            'coupures' => M2Listing::paginate(
                Coupure::with('quartier')->withCount('signalements')->where('statut', 'active'),
                $request->validated(), true, true,
            ),
        ]);
    }

    public function create(): View
    {
        return view('signalements.resident-create', [
            'quartiers' => auth()->user()->role === 'USER' ? Quartier::orderBy('ville')->orderBy('nom')->get(['id', 'nom', 'ville']) : collect(),
            'coupures' => Coupure::with('quartier')->where('statut', 'active')->latest('date_debut')->get(),
        ]);
    }

    public function store(ResidentSignalementRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->safe()->only(['coupure_id', 'adresse', 'description']);
            if (filled($data['coupure_id'] ?? null) && ! Coupure::whereKey($data['coupure_id'])
                ->where('statut', 'active')->lockForUpdate()->first()) {
                throw ValidationException::withMessages(['coupure_id' => 'Cette coupure n’est plus active. Choisissez-en une autre ou poursuivez sans association.']);
            }
            Signalement::create($data + ['user_id' => auth()->id(), 'statut' => 'en attente']);
        });

        return redirect()->route('outages')->with('status', __('Report submitted.'));
    }
}
