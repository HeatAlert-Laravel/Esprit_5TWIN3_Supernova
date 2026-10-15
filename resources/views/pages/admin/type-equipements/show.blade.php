@extends('layouts.app')
@section('title', 'Equipment type details')
@section('content')
@php($count = $type->sensitiveEquipments->count())
<x-ha.page-header :title="$type->name" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Equipment types', route('admin.type-equipements.index')], [$type->name, null]]">
    <x-slot:badges><x-ha.risk-badge :level="$type->risk_level" /></x-slot:badges>
    <x-slot:actions>
        <a href="{{ route('admin.type-equipements.edit', $type) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form novalidate method="POST" action="{{ route('admin.type-equipements.destroy', $type) }}" onsubmit="return confirm('Delete this equipment type?')">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete type</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <section class="ha-card" aria-labelledby="type-info">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="type-info"><span class="ha-icon-chip"><x-ha.icon name="thermometer" /></span>Type information</h2></div>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Name</dt><dd>{{ $type->name }}</dd></div>
            <div><dt>Risk level</dt><dd><x-ha.risk-badge :level="$type->risk_level" short /></dd></div>
            <div><dt>Heat sensitivity</dt><dd><x-ha.sensitivity kind="heat" :on="$type->sensitive_to_heat" /></dd></div>
            <div><dt>Outage sensitivity</dt><dd><x-ha.sensitivity kind="outage" :on="$type->sensitive_to_outage" /></dd></div>
        </dl>
        <x-ha.divider>Record metadata</x-ha.divider>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Created</dt><dd class="ha-mono">{{ $type->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div><dt>Last updated</dt><dd class="ha-mono">{{ $type->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="ha-card" aria-labelledby="type-equipment">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="type-equipment"><span class="ha-icon-chip"><x-ha.icon name="plug" /></span>Equipment using this type <span class="ha-count-pill">{{ $count }}</span></h2>
        </div>
        {{-- $type->sensitiveEquipments: the TypeEquipement hasMany SensitiveEquipment relation. --}}
        @forelse($type->sensitiveEquipments as $item)
            @if($loop->first)<ul class="ha-list">@endif
            <li>
                <div class="min-w-0">
                    <a class="ha-cell-person__name" href="{{ route('admin.equipment.show', $item) }}">{{ $item->name }}</a>
                    <span class="ha-cell-person__sub">{{ $item->profile->user->name }} · {{ $item->profile->neighborhood }}</span>
                </div>
            </li>
            @if($loop->last)</ul>@endif
        @empty
            <x-ha.empty-state icon="plug" title="No equipment uses this type" description="This type is not used yet, so it can be deleted safely." />
        @endforelse
    </section>
</div>
@endsection
