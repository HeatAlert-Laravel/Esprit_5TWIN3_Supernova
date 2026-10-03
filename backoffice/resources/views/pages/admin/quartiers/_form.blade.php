@csrf
@if(isset($quartier)) @method('PUT') @endif

<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="nom" label="Name" :value="old('nom', $quartier?->nom)" required maxlength="255" />
    <x-ha.input name="ville" label="City" :value="old('ville', $quartier?->ville)" required maxlength="255" />
    <x-ha.input name="code_postal" label="Postal code" :value="old('code_postal', $quartier?->code_postal)" required maxlength="10" />
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.quartiers.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save neighborhood</button>
</div>
