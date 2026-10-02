@csrf
@if(isset($equipment)) @method('PUT') @endif

<x-ha.divider>Device</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="name" label="Name" :value="old('name', $equipment?->name)" required help="For example: fan, medication fridge, oxygen concentrator." />
    <x-ha.input name="type" label="Type" :value="old('type', $equipment?->type)" required help="Free text, such as household or medical." />
</div>

<x-ha.divider>Priority and details</x-ha.divider>
<div class="ha-form-grid">
    <x-ha.priority-picker :selected="old('priority_level', $equipment?->priority_level)" />
    <x-ha.field name="description" label="Description" optional help="Anything that helps describe how you use this equipment.">
        <textarea id="description" name="description" rows="4" class="ha-textarea" @error('description') aria-invalid="true" @enderror aria-describedby="description-help @error('description')description-error @enderror">{{ old('description', $equipment?->description) }}</textarea>
    </x-ha.field>
</div>

<div class="ha-form-actions">
    <a href="{{ route('my-profile') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save equipment</button>
</div>
