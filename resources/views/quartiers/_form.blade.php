@csrf
@if(isset($quartier)) @method('PUT') @endif

<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="nom" label="Name" :value="old('nom', $quartier?->nom)" help="Unique within the selected city." />
    <x-ha.input name="ville" label="City" :value="old('ville', $quartier?->ville)" />
    <x-ha.input name="gouvernorat" label="Governorate" :value="old('gouvernorat', $quartier?->gouvernorat)" help="Optional, for example Tunis or Ariana." />
    <x-ha.input name="code_postal" label="Postal code" :value="old('code_postal', $quartier?->code_postal)" help="Numbers only — exactly 4 digits, for example 1053." />
    <x-ha.input name="latitude" label="Latitude" type="number" step="any" :value="old('latitude', $quartier?->latitude)" help="Optional, between -90 and 90. Used for live weather and maps." />
    <x-ha.input name="longitude" label="Longitude" type="number" step="any" :value="old('longitude', $quartier?->longitude)" help="Optional, between -180 and 180. Fill both or neither." />
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.quartiers.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save neighborhood</button>
</div>
