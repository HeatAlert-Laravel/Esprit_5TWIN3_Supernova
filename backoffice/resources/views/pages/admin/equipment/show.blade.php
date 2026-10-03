@extends('layouts.app')
@section('title', 'Equipment details')
@section('content')
@php
    $owner = $equipment->profile;
    $type = $equipment->typeEquipement;
@endphp
<x-ha.page-header :title="$equipment->name" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Sensitive equipment', route('admin.equipment.index')], [$equipment->name, null]]">
    <x-slot:badges>
        <x-ha.risk-badge :level="$type->risk_level" />
        <a href="{{ route('admin.type-equipements.show', $type) }}" class="ha-tag">{{ $type->name }}</a>
    </x-slot:badges>
    <x-slot:actions>
        <a href="{{ route('admin.equipment.edit', $equipment) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.equipment.destroy', $equipment) }}" onsubmit="return confirm('Delete this equipment?')">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete equipment</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <section class="ha-card" aria-labelledby="equipment-info">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="equipment-info"><span class="ha-icon-chip"><x-ha.icon name="plug" /></span>Equipment information</h2></div>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Name</dt><dd>{{ $equipment->name }}</dd></div>
            <div><dt>Type</dt><dd><a href="{{ route('admin.type-equipements.show', $type) }}">{{ $type->name }}</a></dd></div>
            <div><dt>Risk level</dt><dd><x-ha.risk-badge :level="$type->risk_level" short /></dd></div>
            <div><dt>Heat sensitivity</dt><dd><x-ha.sensitivity kind="heat" :on="$type->sensitive_to_heat" /></dd></div>
            <div><dt>Outage sensitivity</dt><dd><x-ha.sensitivity kind="outage" :on="$type->sensitive_to_outage" /></dd></div>
            <div class="ha-span-2"><dt>Description</dt>@if(filled($equipment->description))<dd>{{ $equipment->description }}</dd>@else<dd class="ha-empty-value">Not provided</dd>@endif</div>
            @if($equipment->preparednessMessages())<div class="ha-span-2"><dt>Preparedness</dt><dd><x-ha.preparedness :equipment="$equipment" /></dd></div>@endif
        </dl>
        <x-ha.divider>Record metadata</x-ha.divider>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Equipment ID</dt><dd class="ha-mono">{{ $equipment->id }}</dd></div>
            <div><dt>Last updated</dt><dd class="ha-mono">{{ $equipment->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="ha-card" aria-labelledby="equipment-owner">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="equipment-owner"><span class="ha-icon-chip"><x-ha.icon name="home" /></span>Owner</h2></div>
        <div class="ha-cell-person">
            <x-ha.avatar :name="$owner->user->name" size="lg" />
            <div class="min-w-0">
                <a class="ha-cell-person__name" href="{{ route('admin.profiles.show', $owner) }}">{{ $owner->user->name }}</a>
                <span class="ha-cell-person__sub">{{ $owner->neighborhood }}</span>
            </div>
        </div>
        <dl class="ha-dl mt-5">
            <div><dt>Neighborhood</dt><dd>@if($owner->neighborhood)<span class="ha-tag">{{ $owner->neighborhood }}</span>@else<span class="ha-empty-value">Not provided</span>@endif</dd></div>
            <div><dt>Email</dt><dd>{{ $owner->user->email }}</dd></div>
            <div><dt>Phone</dt>@if(filled($owner->phone))<dd>{{ $owner->phone }}</dd>@else<dd class="ha-empty-value">Not provided</dd>@endif</div>
        </dl>
        <a href="{{ route('admin.profiles.show', $owner) }}" class="ha-btn ha-btn--outline ha-btn--sm mt-5">View profile<x-ha.icon name="arrow-right" size="sm" /></a>
    </section>
</div>
@endsection
