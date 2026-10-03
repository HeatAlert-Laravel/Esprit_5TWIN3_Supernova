<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlerteMeteoController extends Controller
{
    public function index(Request $request): View
    {
        $quartierId = (string) $request->query('quartier_id', '');

        return view('pages.admin.alertes-meteo.index', [
            'alertes' => AlerteMeteo::with('quartier')->when($quartierId !== '', fn ($query) => $query->where('quartier_id', $quartierId))->latest('date_debut')->paginate(10)->withQueryString(),
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $quartierId,
            'totalAlertes' => AlerteMeteo::count(),
        ]);
    }

    public function create(): View
    {
        return view('pages.admin.alertes-meteo.create', ['quartiers' => $this->quartiers()]);
    }

    public function store(Request $request): RedirectResponse
    {
        AlerteMeteo::create($this->validated($request));

        return redirect()->route('admin.alertes-meteo.index')->with('status', 'Weather alert created.');
    }

    public function show(AlerteMeteo $alerte): View
    {
        return view('pages.admin.alertes-meteo.show', ['alerte' => $alerte->load('quartier')]);
    }

    public function edit(AlerteMeteo $alerte): View
    {
        return view('pages.admin.alertes-meteo.edit', ['alerte' => $alerte, 'quartiers' => $this->quartiers()]);
    }

    public function update(Request $request, AlerteMeteo $alerte): RedirectResponse
    {
        $alerte->update($this->validated($request));

        return redirect()->route('admin.alertes-meteo.show', $alerte)->with('status', 'Weather alert updated.');
    }

    public function destroy(AlerteMeteo $alerte): RedirectResponse
    {
        $alerte->delete();

        return redirect()->route('admin.alertes-meteo.index')->with('status', 'Weather alert deleted.');
    }

    private function quartiers()
    {
        return Quartier::orderBy('ville')->orderBy('nom')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'quartier_id' => ['required', 'exists:quartiers,id'],
            'titre' => ['required', 'string', 'max:255'],
            'niveau' => ['required', 'in:vert,jaune,orange,rouge'],
            'temperature_max' => ['required', 'numeric', 'between:-50,60'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'publiee' => ['sometimes', 'boolean'],
        ]) + ['publiee' => $request->boolean('publiee')];
    }
}