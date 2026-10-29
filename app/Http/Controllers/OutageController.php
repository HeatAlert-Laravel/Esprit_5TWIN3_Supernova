<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentSignalementRequest;
use App\Http\Requests\M2IndexRequest;
use App\Support\M2Listing;
use App\Models\Coupure;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
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
            'coupures' => Coupure::with('quartier')->where('statut', 'active')->latest('date_debut')->get(),
        ]);
    }

    public function store(ResidentSignalementRequest $request): RedirectResponse
    {
        Signalement::create($request->safe()->only(['coupure_id', 'adresse', 'description'])
            + ['user_id' => auth()->id(), 'statut' => 'en attente']);

        return redirect()->route('outages')->with('status', __('Report submitted.'));
    }
}
