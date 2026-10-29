@csrf
@if(isset($alerte)) @method('PUT') @endif

<div class="ha-form-grid ha-form-grid--2">
    <x-ha.field name="quartier_id" label="Neighborhood">
        <select id="quartier_id" name="quartier_id" class="ha-select">
            <option value="">Choose a neighborhood</option>
            @foreach($quartiers as $quartier)
                <option value="{{ $quartier->id }}" @selected((string) old('quartier_id', $alerte?->quartier_id) === (string) $quartier->id)>{{ $quartier->nom }} - {{ $quartier->ville }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <x-ha.input name="titre" label="Title" :value="old('titre', $alerte?->titre)" />
    <x-ha.field name="type" label="Type">
        <select id="type" name="type" class="ha-select">
            @foreach(\App\Models\AlerteMeteo::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $alerte?->type ?? 'canicule') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <x-ha.field name="niveau" label="Level">
        <select id="niveau" name="niveau" class="ha-select">
            @foreach(['vert' => 'Green', 'jaune' => 'Yellow', 'orange' => 'Orange', 'rouge' => 'Red'] as $value => $label)
                <option value="{{ $value }}" @selected(old('niveau', $alerte?->niveau) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <x-ha.input name="temperature_max" label="Maximum temperature (°C)" type="number" step="any" :value="old('temperature_max', $alerte?->temperature_max)" help="Decimals are allowed, for example 32.5." />
    <x-ha.input name="temperature_min" label="Minimum temperature (°C)" type="number" step="any" :value="old('temperature_min', $alerte?->temperature_min)" help="Optional. Cannot exceed the maximum." />
    <x-ha.input name="temperature_ressentie" label="Feels-like temperature (°C)" type="number" step="any" :value="old('temperature_ressentie', $alerte?->temperature_ressentie)" help="Optional." />
    <x-ha.input name="humidite" label="Humidity (%)" type="number" min="0" max="100" :value="old('humidite', $alerte?->humidite)" help="Optional, 0 to 100." />
    <x-ha.input name="indice_uv" label="UV index" type="number" min="0" max="15" :value="old('indice_uv', $alerte?->indice_uv)" help="Optional, 0 to 15." />
    <x-ha.input name="vitesse_vent" label="Wind speed (km/h)" type="number" step="any" min="0" :value="old('vitesse_vent', $alerte?->vitesse_vent)" help="Optional." />
    <x-ha.input name="source" label="Source" :value="old('source', $alerte?->source)" help="Optional, for example the weather service." />
    <x-ha.input name="date_debut" label="Start date" type="date" :value="old('date_debut', $alerte?->date_debut?->format('Y-m-d'))" />
    <x-ha.input name="date_fin" label="End date" type="date" :value="old('date_fin', $alerte?->date_fin?->format('Y-m-d'))" help="Must be on or after the start date." />
</div>
<div class="ha-field">
    <label class="ha-check"><input type="hidden" name="risque_coupure" value="0"><input type="checkbox" name="risque_coupure" value="1" @checked(old('risque_coupure', $alerte?->risque_coupure))><span class="ha-check__text">Power outage risk (heat may overload the grid)</span></label>
    @error('risque_coupure')<p class="ha-error">{{ $message }}</p>@enderror
</div>
<div class="ha-field">
    <label class="ha-check"><input type="hidden" name="publiee" value="0"><input type="checkbox" name="publiee" value="1" @checked(old('publiee', $alerte?->publiee))><span class="ha-check__text">Publish this alert</span></label>
    @error('publiee')<p class="ha-error">{{ $message }}</p>@enderror
</div>
<div class="ha-form-actions">
    <a href="{{ route('admin.alertes-meteo.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save weather alert</button>
</div>
