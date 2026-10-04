@csrf
@if(isset($point)) @method('PUT') @endif

<x-ha.divider>Identification</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="nom" label="Cooling point name" :value="old('nom', $point?->nom)" required maxlength="150" help="For example: Belvédère Park, Central Mist Fountain." />
    
    <div class="ha-field">
        <label class="ha-label" for="type_point_id">Point type <span class="text-red-500">*</span></label>
        <select id="type_point_id" name="type_point_id" required class="ha-select" @error('type_point_id') aria-invalid="true" @enderror>
            <option value="">Choose a point type</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}" @selected((string)old('type_point_id', $point?->type_point_id ?? request('type_id')) === (string)$type->id)>{{ $type->nom }}</option>
            @endforeach
        </select>
        @error('type_point_id')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<x-ha.divider>Location & Hours</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <div class="ha-field">
        <label class="ha-label" for="quartier_id">Neighborhood (Member 1) <span class="text-red-500">*</span></label>
        <select id="quartier_id" name="quartier_id" required class="ha-select" @error('quartier_id') aria-invalid="true" @enderror>
            <option value="">Choose a neighborhood</option>
            @foreach($quartiers as $quartier)
                <option value="{{ $quartier->id }}" @selected((string)old('quartier_id', $point?->quartier_id ?? request('quartier_id')) === (string)$quartier->id)>{{ $quartier->nom }} ({{ $quartier->ville }})</option>
            @endforeach
        </select>
        @error('quartier_id')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>

    <x-ha.input name="adresse" label="Address" :value="old('adresse', $point?->adresse)" required maxlength="255" help="Precise street address." />
</div>

<div class="ha-form-grid ha-form-grid--3">
    <x-ha.input name="latitude" label="Latitude (GPS)" type="number" step="0.0000001" :value="old('latitude', $point?->latitude)" placeholder="36.8189" />
    <x-ha.input name="longitude" label="Longitude (GPS)" type="number" step="0.0000001" :value="old('longitude', $point?->longitude)" placeholder="10.1747" />
    <x-ha.input name="horaires" label="Opening hours" :value="old('horaires', $point?->horaires)" placeholder="e.g. 08:00 - 20:00 or 24/7" />
</div>

<x-ha.divider>Accessibility & Status</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <div class="ha-field">
        <label class="ha-check">
            <input type="checkbox" name="accessible_pmr" value="1" @checked(old('accessible_pmr', $point?->accessible_pmr ?? false))>
            <span class="ha-check__text">
                PRM Accessible
                <small>Wheelchair and reduced-mobility accessible (ramps, ground-level entry, elevator).</small>
            </span>
        </label>
        @error('accessible_pmr')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>

    <div class="ha-field">
        <label class="ha-check">
            <input type="checkbox" name="actif" value="1" @checked(old('actif', $point?->actif ?? true))>
            <span class="ha-check__text">
                Active / open location
                <small>Visible to residents on the Front Office when checked.</small>
            </span>
        </label>
        @error('actif')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-grid">
    <div class="ha-field">
        <label class="ha-label" for="description">Description / Resident instructions</label>
        <textarea id="description" name="description" rows="3" class="ha-input" placeholder="Additional details, drinking water availability, cool indoor spots...">{{ old('description', $point?->description) }}</textarea>
        @error('description')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.point-fraicheurs.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save cooling point</button>
</div>
