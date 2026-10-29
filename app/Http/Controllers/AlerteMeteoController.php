<?php

namespace App\Http\Controllers;

use App\Models\AlerteMeteo;
use App\Models\Quartier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlerteMeteoController extends Controller
{
    public function index(Request $request): View
    {
        $quartierId = (string) $request->query('quartier_id', '');
        $origine = in_array($request->query('origine'), ['auto', 'manual'], true) ? $request->query('origine') : '';

        return view('alertes-meteo.index', [
            'alertes' => AlerteMeteo::with('quartier')->when($quartierId !== '', fn ($query) => $query->where('quartier_id', $quartierId))->when($origine === 'auto', fn ($query) => $query->automatic())->when($origine === 'manual', fn ($query) => $query->manual())->latest('date_debut')->paginate(10)->withQueryString(),
            'quartiers' => Quartier::orderBy('ville')->orderBy('nom')->get(),
            'quartierId' => $quartierId,
            'origine' => $origine,
            'totalAlertes' => AlerteMeteo::count(),
            'totalAutomatiques' => AlerteMeteo::automatic()->count(),
        ]);
    }

    public function create(): View
    {
        return view('alertes-meteo.create', ['quartiers' => $this->quartiers()]);
    }

    public function store(Request $request): RedirectResponse
    {
        AlerteMeteo::create($this->validated($request));

        return redirect()->route('admin.alertes-meteo.index')->with('status', 'Weather alert created.');
    }

    public function show(AlerteMeteo $alerte): View
    {
        return view('alertes-meteo.show', ['alerte' => $alerte->load('quartier')]);
    }

    public function edit(AlerteMeteo $alerte): View
    {
        return view('alertes-meteo.edit', ['alerte' => $alerte, 'quartiers' => $this->quartiers()]);
    }

    public function update(Request $request, AlerteMeteo $alerte): RedirectResponse
    {
        $wasAutomatic = $alerte->generee_auto;

        // An administrator's edit takes ownership: the generator never overwrites a manual alert.
        $alerte->update($this->validated($request) + ['generee_auto' => false]);

        return redirect()->route('admin.alertes-meteo.show', $alerte)->with('status', $wasAutomatic
            ? 'Weather alert updated. It is now managed manually and will no longer be changed by the automatic generation.'
            : 'Weather alert updated.');
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
            'type' => ['nullable', Rule::in(array_keys(AlerteMeteo::TYPES))],
            'niveau' => ['required', 'in:vert,jaune,orange,rouge'],
            'temperature_max' => ['required', 'numeric', 'between:-50,60'],
            'temperature_min' => ['nullable', 'numeric', 'between:-50,60', 'lte:temperature_max'],
            'temperature_ressentie' => ['nullable', 'numeric', 'between:-50,70'],
            'humidite' => ['nullable', 'integer', 'between:0,100'],
            'indice_uv' => ['nullable', 'integer', 'between:0,15'],
            'vitesse_vent' => ['nullable', 'numeric', 'between:0,400'],
            'source' => ['nullable', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'publiee' => ['sometimes', 'boolean'],
        ], [], [
            'quartier_id' => 'neighborhood',
            'titre' => 'title',
            'niveau' => 'level',
            'temperature_max' => 'maximum temperature',
            'temperature_min' => 'minimum temperature',
            'temperature_ressentie' => 'feels-like temperature',
            'humidite' => 'humidity',
            'indice_uv' => 'UV index',
            'vitesse_vent' => 'wind speed',
            'date_debut' => 'start date',
            'date_fin' => 'end date',
        ]) + ['type' => $request->input('type') ?: 'canicule', 'publiee' => $request->boolean('publiee'), 'risque_coupure' => $request->boolean('risque_coupure')];
    }
}
