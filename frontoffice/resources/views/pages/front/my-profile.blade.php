@extends('layouts.front')
@section('title', 'My Profile')
@section('content')
@php
    $user = auth()->user();
    $completion = $profile?->completionPercent() ?? 0;
@endphp
<header class="ha-profile-head">
    <div class="ha-profile-head__id">
        <x-ha.avatar :name="$user->name" size="lg" />
        <div class="min-w-0">
            <p class="ha-eyebrow">Resident area</p>
            <h1>{{ $user->name }}</h1>
            <p class="ha-muted">{{ $user->email }}</p>
        </div>
    </div>
    <div class="ha-profile-head__meter">
        <div class="ha-row-between">
            <span>Profile details</span>
            <x-ha.badge :variant="$profile?->isComplete() ? 'success' : 'warning'">{{ $profile?->isComplete() ? 'Complete' : 'Incomplete' }} · {{ $completion }}%</x-ha.badge>
        </div>
        <div class="ha-progress" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100" aria-label="Profile completion"><div class="ha-progress__bar" style="width: {{ $completion }}%"></div></div>
    </div>
</header>

<div class="ha-grid ha-grid--main">
    <section class="ha-card" aria-labelledby="details-title">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="details-title"><span class="ha-icon-chip"><x-ha.icon name="home" /></span>Household details</h2></div>
        <p class="ha-muted">Keep these details up to date so your household information is complete for local heat planning.</p>
        <x-ha.error-summary />
        <form novalidate method="POST" action="{{ route('my-profile.update') }}">
            @csrf
            @method('PUT')
            <x-ha.divider>Contact</x-ha.divider>
            <div class="ha-form-grid">
                <x-ha.input name="phone" label="Phone" type="tel" autocomplete="tel" :value="old('phone', $profile?->phone)" required />
            </div>
            <x-ha.divider>Household</x-ha.divider>
            <div class="ha-form-grid ha-form-grid--2">
                <x-ha.input name="address" label="Address" autocomplete="street-address" :value="old('address', $profile?->address)" required wrapper-class="ha-span-2" />
                <x-ha.input name="neighborhood" label="Neighborhood" :value="old('neighborhood', $profile?->neighborhood)" required wrapper-class="ha-span-2" />
            </div>
            <x-ha.divider>Household members</x-ha.divider>
            <div class="ha-field">
                <input type="hidden" name="has_fragile_person" value="0">
                <label class="ha-check"><input type="checkbox" name="has_fragile_person" value="1" {{ old('has_fragile_person', $profile?->has_fragile_person) ? 'checked' : '' }}><span class="ha-check__text">A fragile person lives in this household<small>Tick this if someone in your household may need extra attention in hot weather.</small></span></label>
                @error('has_fragile_person')<p class="ha-error"><x-ha.icon name="alert-triangle" size="sm" />{{ $message }}</p>@enderror
            </div>
            <div class="ha-form-actions">
                <button class="ha-btn ha-btn--primary">Save profile</button>
            </div>
        </form>
    </section>

    <div class="ha-stack">
        @include('partials.readiness-card')
    </div>
</div>

<section class="ha-section" id="equipment" aria-labelledby="equipment-title">
    <div class="ha-page-header">
        <div>
            <h2 class="ha-card__title ha-card__title--with-icon text-2xl" id="equipment-title">Sensitive equipment @if($profile)<span class="ha-count-pill">{{ $profile->sensitiveEquipments->count() }}</span>@endif</h2>
            <p class="ha-page-header__desc">Devices linked to your household profile that need power or cooling, such as a fan, a medication fridge or an oxygen concentrator.</p>
        </div>
        @if($profile)
            <div class="ha-page-header__actions"><a href="{{ route('profile.equipment.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add equipment</a></div>
        @endif
    </div>

    @if($profile)
        @if($profile->sensitiveEquipments->isEmpty())
            <div class="ha-card">
                <x-ha.empty-state icon="plug" title="No equipment recorded yet." description="Add devices that need power or cooling during hot weather. Examples are only suggestions: you decide what to record.">
                    <a href="{{ route('profile.equipment.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add equipment</a>
                </x-ha.empty-state>
            </div>
        @else
            <ul class="ha-devices" role="list">
                @foreach($profile->sensitiveEquipments as $item)
                    <li class="ha-device">
                        <div class="ha-device__head">
                            <span class="ha-icon-chip"><x-ha.icon name="plug" /></span>
                            <div class="min-w-0">
                                <h3 class="ha-device__name">{{ $item->name }}</h3>
                                <div class="ha-device__tags">
                                    <span class="ha-tag">Type: {{ $item->typeEquipement->name }}</span>
                                    <x-ha.risk-badge :level="$item->typeEquipement->risk_level" />
                                </div>
                                <div class="ha-device__tags">
                                    <x-ha.sensitivity kind="heat" :on="$item->typeEquipement->sensitive_to_heat" />
                                    <x-ha.sensitivity kind="outage" :on="$item->typeEquipement->sensitive_to_outage" />
                                </div>
                            </div>
                        </div>
                        @if($item->description)<p class="ha-device__desc">{{ $item->description }}</p>@else<p class="ha-device__desc">No notes added.</p>@endif
                        <x-ha.preparedness :equipment="$item" />
                        <div class="ha-device__actions">
                            <a href="{{ route('profile.equipment.edit', $item) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="Edit {{ $item->name }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                            <form novalidate method="POST" action="{{ route('profile.equipment.destroy', $item) }}" onsubmit="return confirm('Delete this equipment?')">
                                @csrf
                                @method('DELETE')
                                <button class="ha-btn ha-btn--danger-soft ha-btn--sm" aria-label="Remove {{ $item->name }}"><x-ha.icon name="trash" size="sm" />Remove</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    @else
        <div class="ha-alert ha-alert--info"><x-ha.icon name="info" /><div>Save your household details first to add sensitive equipment.</div></div>
    @endif
</section>
@endsection
