@csrf
@if(isset($type)) @method('PUT') @endif

<x-ha.divider>Type</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="name" label="Name" :value="old('name', $type?->name)" required maxlength="100" help="For example: Refrigerator, Medical equipment, Fan." />
    <x-ha.field name="risk_level" label="Risk level" help="One risk level shared by every equipment of this type.">
        <select id="risk_level" name="risk_level" required class="ha-select" @error('risk_level') aria-invalid="true" @enderror aria-describedby="risk_level-help @error('risk_level')risk_level-error @enderror">
            <option value="">Choose a risk level</option>
            @foreach(\App\Models\TypeEquipement::RISK_LEVELS as $level)
                <option value="{{ $level }}" @selected(old('risk_level', $type?->risk_level) === $level)>{{ ucfirst($level) }}</option>
            @endforeach
        </select>
    </x-ha.field>
</div>

<x-ha.divider>Sensitivity</x-ha.divider>
<div class="ha-form-grid">
    <div class="ha-field">
        <label class="ha-check"><input type="checkbox" name="sensitive_to_heat" value="1" @checked(old('sensitive_to_heat', $type?->sensitive_to_heat))><span class="ha-check__text">Sensitive to heat<small>This kind of equipment may need protection during high temperatures.</small></span></label>
        @error('sensitive_to_heat')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
    <div class="ha-field">
        <label class="ha-check"><input type="checkbox" name="sensitive_to_outage" value="1" @checked(old('sensitive_to_outage', $type?->sensitive_to_outage))><span class="ha-check__text">Sensitive to power outage<small>This kind of equipment depends on electricity.</small></span></label>
        @error('sensitive_to_outage')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.type-equipements.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save type</button>
</div>
