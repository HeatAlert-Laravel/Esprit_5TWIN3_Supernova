@csrf
@if(isset($equipment)) @method('PUT') @endif
<x-ha.divider>Owner</x-ha.divider>
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
    <x-ha.input name="name" label="Name" :value="old('name', $equipment?->name)" required maxlength="255" help="For example: Refrigerator, Freezer, Oxygen concentrator." />
    <x-ha.input name="type" label="Type" :value="old('type', $equipment?->type)" required maxlength="100" help="Free text, such as household or medical." />
</div>

<x-ha.divider>Priority and details</x-ha.divider>
<div class="ha-form-grid">
    <x-ha.priority-picker :selected="old('priority_level', $equipment?->priority_level)" />
    <x-ha.field name="description" label="Description" optional help="Anything an administrator should know about this equipment.">
        <textarea id="description" name="description" rows="4" class="ha-textarea" @error('description') aria-invalid="true" @enderror aria-describedby="description-help @error('description')description-error @enderror">{{ old('description', $equipment?->description) }}</textarea>
    </x-ha.field>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save equipment</button>
</div>
