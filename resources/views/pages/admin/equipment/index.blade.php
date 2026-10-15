@extends('layouts.app')
@section('title', 'Sensitive equipment')
@section('content')
@php($isFiltered = collect($filters)->contains(fn ($value) => $value !== ''))
<x-ha.page-header title="Sensitive equipment" description="Devices residents rely on during heat and power cuts, with the household that owns them." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Sensitive equipment', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $isFiltered ? $equipment->total().' of '.$totalEquipment : $totalEquipment }} {{ $isFiltered ? \Illuminate\Support\Str::plural('record', $equipment->total()) : \Illuminate\Support\Str::plural('record', $totalEquipment) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.equipment.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add equipment</a></x-slot:actions>
</x-ha.page-header>

<form novalidate method="GET" action="{{ route('admin.equipment.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="Filter equipment">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Equipment or resident</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Search by equipment name, resident name or email">
    </div>
    <div class="ha-field">
        <label class="ha-label" for="type-filter">Type</label>
        <select class="ha-select" id="type-filter" name="type">
            <option value="">All types</option>
            @foreach($types as $type)<option value="{{ $type->id }}" @selected($filters['type'] === (string) $type->id)>{{ $type->name }}</option>@endforeach
        </select>
    </div>
    <div class="ha-field">
        <label class="ha-label" for="risk-filter">Risk</label>
        <select class="ha-select" id="risk-filter" name="risk">
            <option value="">All risk levels</option>
            @foreach(array_reverse(\App\Models\TypeEquipement::RISK_LEVELS) as $level)<option value="{{ $level }}" @selected($filters['risk'] === $level)>{{ ucfirst($level) }}</option>@endforeach
        </select>
    </div>
    <div class="ha-field">
        <span class="ha-label">Sensitivity</span>
        <label class="ha-check"><input type="checkbox" name="heat" value="1" @checked($filters['heat'] === '1')><span class="ha-check__text">Heat-sensitive</span></label>
        <label class="ha-check"><input type="checkbox" name="outage" value="1" @checked($filters['outage'] === '1')><span class="ha-check__text">Outage-sensitive</span></label>
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary">Apply filters</button></noscript>
        @if($isFiltered)<a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--ghost">Reset filters</a>@endif
    </div>
</form>

@if($equipment->isEmpty())
    <div class="ha-card">
        @if($isFiltered)
            <x-ha.empty-state icon="search" title="No equipment matches these filters" description="Try a different search, type, risk level or sensitivity.">
                <a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--outline">Reset filters</a>
            </x-ha.empty-state>
        @else
            <x-ha.empty-state icon="plug" title="No sensitive equipment has been added yet." description="Equipment appears here once residents record it, or when you add it for a household.">
                <a href="{{ route('admin.equipment.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add equipment</a>
            </x-ha.empty-state>
        @endif
    </div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead>
                <tr>
                    <th scope="col">Equipment</th>
                    <th scope="col">Resident</th>
                    <th scope="col">Type</th>
                    <th scope="col">Heat</th>
                    <th scope="col">Outage</th>
                    <th scope="col">Risk</th>
                    <th scope="col">Updated</th>
                    <th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipment as $item)
                    <tr>
                        <td>
                            <div class="ha-cell-person">
                                <span class="ha-icon-chip"><x-ha.icon name="plug" /></span>
                                <div>
                                    <a href="{{ route('admin.equipment.show', $item) }}" class="ha-cell-person__name">{{ $item->name }}</a>
                                    @if($item->description)<span class="ha-cell-person__sub">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</span>@endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.profiles.show', $item->profile) }}" class="ha-cell-person__name">{{ $item->profile->user->name }}</a>
                            <span class="ha-cell-person__sub">{{ $item->profile->neighborhood }}</span>
                        </td>
                        <td><a href="{{ route('admin.type-equipements.show', $item->typeEquipement) }}" class="ha-tag">{{ $item->typeEquipement->name }}</a></td>
                        <td><x-ha.sensitivity kind="heat" :on="$item->typeEquipement->sensitive_to_heat" /></td>
                        <td><x-ha.sensitivity kind="outage" :on="$item->typeEquipement->sensitive_to_outage" /></td>
                        <td><x-ha.risk-badge :level="$item->typeEquipement->risk_level" short /></td>
                        <td><time class="ha-mono" datetime="{{ $item->updated_at?->toDateString() }}">{{ $item->updated_at?->format('d M Y') ?? '—' }}</time></td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.equipment.show', $item) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="View {{ $item->name }}"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.equipment.edit', $item) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="Edit {{ $item->name }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="ha-pagination">{{ $equipment->links() }}</div>
@endif
@endsection
