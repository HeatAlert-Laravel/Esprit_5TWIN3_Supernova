@extends('layouts.app')
@section('title', 'Profile details')
@section('content')
@php
    $user = $profile->user;
    $notProvided = fn ($value) => filled($value) ? e($value) : '<span class="ha-empty-value">Not provided</span>';
@endphp
<x-ha.page-header :title="$user->name" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Profiles', route('admin.profiles.index')], [$user->name, null]]">
    <x-slot:badges>
        <x-ha.badge :variant="$profile->isComplete() ? 'success' : 'warning'">{{ $profile->isComplete() ? 'Complete' : 'Incomplete' }}</x-ha.badge>
        @if($profile->neighborhood)<span class="ha-tag"><x-ha.icon name="map-pin" size="sm" />{{ $profile->neighborhood }}</span>@endif
    </x-slot:badges>
    <x-slot:actions>
        <a href="{{ route('admin.profiles.edit', $profile) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.profiles.destroy', $profile) }}" onsubmit="return confirm('Delete this profile?')">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete profile</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <div class="ha-stack">
        <section class="ha-card" aria-labelledby="identity-title">
            <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="identity-title"><span class="ha-icon-chip"><x-ha.icon name="user" /></span>Identity</h2></div>
            <dl class="ha-dl ha-dl--2">
                <div><dt>Name</dt><dd>{!! $notProvided($user->name) !!}</dd></div>
                <div><dt>Email</dt><dd>{!! $notProvided($user->email) !!}</dd></div>
                <div><dt>Account role</dt><dd>{{ ucfirst(strtolower($user->role)) }}</dd></div>
            </dl>
        </section>

        <section class="ha-card" aria-labelledby="contact-title">
            <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="contact-title"><span class="ha-icon-chip"><x-ha.icon name="phone" /></span>Contact</h2></div>
            <dl class="ha-dl ha-dl--2">
                <div><dt>Phone</dt><dd>{!! $notProvided($profile->phone) !!}</dd></div>
                <div><dt>Address</dt><dd>{!! $notProvided($profile->address) !!}</dd></div>
            </dl>
        </section>

        <section class="ha-card" aria-labelledby="household-title">
            <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="household-title"><span class="ha-icon-chip"><x-ha.icon name="home" /></span>Household</h2></div>
            <dl class="ha-dl ha-dl--2">
                <div><dt>Neighborhood</dt><dd>{!! $notProvided($profile->neighborhood) !!}</dd></div>
                <div><dt>Fragile person</dt><dd>{{ $profile->has_fragile_person ? 'Yes' : 'No' }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="ha-stack">
        <section class="ha-card" aria-labelledby="equipment-title">
            <div class="ha-card__head">
                <h2 class="ha-card__title ha-card__title--with-icon" id="equipment-title"><span class="ha-icon-chip"><x-ha.icon name="plug" /></span>Sensitive equipment <span class="ha-count-pill">{{ $profile->sensitiveEquipments->count() }}</span></h2>
                <a class="ha-btn ha-btn--outline ha-btn--sm" href="{{ route('admin.equipment.create', ['profile_id' => $profile->id]) }}"><x-ha.icon name="plus" size="sm" />Add</a>
            </div>
            @forelse($profile->sensitiveEquipments as $item)
                @if($loop->first)<ul class="ha-list">@endif
                <li>
                    <div class="min-w-0">
                        <a class="ha-cell-person__name" href="{{ route('admin.equipment.show', $item) }}">{{ $item->name }}</a>
                        <span class="ha-tag">{{ $item->typeEquipement->name }}</span>
                    </div>
                    <x-ha.risk-badge :level="$item->typeEquipement->risk_level" />
                </li>
                @if($loop->last)</ul>@endif
            @empty
                <x-ha.empty-state icon="plug" title="No equipment linked" description="This household has not recorded any sensitive equipment." />
            @endforelse
        </section>

        <section class="ha-card ha-card--compact" aria-labelledby="meta-title">
            <h2 class="ha-card__title mb-3" id="meta-title">Record metadata</h2>
            <dl class="ha-dl">
                <div><dt>Created</dt><dd class="ha-mono">{{ $profile->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
                <div><dt>Last updated</dt><dd class="ha-mono">{{ $profile->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
