@csrf
@if(isset($equipment)) @method('PUT') @endif
<x-ha.divider>Resident</x-ha.divider>
<x-ha.field name="profile_id" label="Household profile" help="The resident household this equipment belongs to.">
    <select id="profile_id" name="profile_id" required class="ha-select" @error('profile_id') aria-invalid="true" @enderror aria-describedby="profile_id-help @error('profile_id')profile_id-error @enderror">
        <option value="">Choose a profile</option>
        @foreach($profiles as $profile)
            <option value="{{ $profile->id }}" {{ (string) old('profile_id', $equipment->profile_id ?? request('profile_id')) === (string) $profile->id ? 'selected' : '' }}>{{ $profile->user->name }} · {{ $profile->neighborhood }}</option>
        @endforeach
    </select>
</x-ha.field>

<x-ha.divider>Device</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="name" label="Name" :value="old('name', $equipment?->name)" required maxlength="255" help="For example: Kitchen refrigerator, Oxygen concentrator." />
    <x-ha.field name="type_equipement_id" label="Type" help="The type sets the risk level and the heat and outage sensitivity.">
        <select id="type_equipement_id" name="type_equipement_id" required class="ha-select" @error('type_equipement_id') aria-invalid="true" @enderror aria-describedby="type_equipement_id-help @error('type_equipement_id')type_equipement_id-error @enderror">
            <option value="">Choose a type</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}" @selected((string) old('type_equipement_id', $equipment?->type_equipement_id) === (string) $type->id)>{{ $type->name }} · {{ ucfirst($type->risk_level) }} risk</option>
            @endforeach
        </select>
    </x-ha.field>
</div>

<x-ha.divider>Notes</x-ha.divider>
<div class="ha-form-grid">
    <x-ha.field name="description" label="Description" optional help="Anything an administrator should know about this equipment.">
        <textarea id="description" name="description" rows="4" class="ha-textarea" @error('description') aria-invalid="true" @enderror aria-describedby="description-help @error('description')description-error @enderror">{{ old('description', $equipment?->description) }}</textarea>
    </x-ha.field>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save equipment</button>
</div>
