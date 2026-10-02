@csrf
@if(isset($profile)) @method('PUT') @endif
<x-ha.divider>Resident</x-ha.divider>
<x-ha.field name="user_id" label="Resident account" help="Each resident account can have one household profile.">
    <select id="user_id" name="user_id" required class="ha-select" @error('user_id') aria-invalid="true" @enderror aria-describedby="user_id-help @error('user_id')user_id-error @enderror">
        <option value="">Choose a user</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ (string) old('user_id', $profile->user_id ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
</x-ha.field>

<x-ha.divider>Contact</x-ha.divider>
<div class="ha-form-grid ha-form-grid--2">
    <x-ha.input name="phone" label="Phone" type="tel" autocomplete="tel" :value="old('phone', $profile?->phone)" required maxlength="20" />
    <x-ha.input name="address" label="Address" :value="old('address', $profile?->address)" required maxlength="255" wrapper-class="ha-span-2" />
</div>

<x-ha.divider>Household</x-ha.divider>
<div class="ha-form-grid">
    <x-ha.input name="neighborhood" label="Neighborhood" :value="old('neighborhood', $profile?->neighborhood)" required maxlength="100" list="neighborhood-suggestions" help="Free text. Existing neighborhoods are suggested to keep spelling consistent." />
    <datalist id="neighborhood-suggestions">@foreach($neighborhoods as $suggestion)<option value="{{ $suggestion }}"></option>@endforeach</datalist>
    <div class="ha-field">
        <input type="hidden" name="has_fragile_person" value="0">
        <label class="ha-check"><input type="checkbox" name="has_fragile_person" value="1" {{ old('has_fragile_person', $profile?->has_fragile_person) ? 'checked' : '' }}><span class="ha-check__text">Fragile person in household</span></label>
        @error('has_fragile_person')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
    </div>
</div>

<div class="ha-form-actions">
    <a href="{{ route('admin.profiles.index') }}" class="ha-btn ha-btn--outline">Cancel</a>
    <button class="ha-btn ha-btn--primary">Save profile</button>
</div>
