@csrf
@if(isset($alerte)) @method('PUT') @endif

<div class="ha-form-grid ha-form-grid--2">
    <x-ha.field name="quartier_id" label="Neighborhood">
        <select id="quartier_id" name="quartier_id" class="ha-select" required>
            <option value="">Choose a neighborhood</option>
            @foreach($quartiers as $quartier)
                <option value="{{ $quartier->id }}" @selected((string) old('quartier_id', $alerte?->quartier_id) === (string) $quartier->id)>{{ $quartier->nom }} - {{ $quartier->ville }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <x-ha.input name="titre" label="Title" :value="old('titre', $alerte?->titre)" required maxlength="255" />
    <x-ha.field name="niveau" label="Level">
        <select id="niveau" name="niveau" class="ha-select" required>
            @foreach(['vert' => 'Green', 'jaune' => 'Yellow', 'orange' => 'Orange', 'rouge' => 'Red'] as $value => $label)
                <option value="{{ $value }}" @selected(old('niveau', $alerte?->niveau) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </x-ha.field>
    <x-ha.input name="temperature_max" label="Maximum temperature (°C)" type="number" step="0.1" :value="old('temperature_max', $alerte?->temperature_max)" required />
    <x-ha.input name="date_debut" label="Start date" type="date" :value="old('date_debut', $alerte?->date_debut?->format('Y-m-d'))" required />
    <x-ha.input name="date_fin" label="End date" type="date" :value="old('date_fin', $alerte?->date_fin?->format('Y-m-d'))" required />
</div>
<div class="ha-field">
    <label class="ha-check"><input type="hidden" name="publiee" value="0"><input type="checkbox" name="publiee" value="1" @checked(old('publiee', $alerte?->publiee))><span class="ha-check__text">Publish this alert</span></label>
    @error('publiee')<p class="ha-error">{{ $message }}</p>@enderror
</div>
<div class="ha-form-actions">
    <a href="{{ route('admin.alertes-meteo.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save weather alert</button>
</div>