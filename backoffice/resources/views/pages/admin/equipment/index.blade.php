@extends('layouts.app')
@section('title', 'Sensitive equipment')
@section('content')
@php($isFiltered = $filters['q'] !== '' || $filters['priority'] !== '' || $filters['type'] !== '')
<x-ha.page-header title="Sensitive equipment" description="Devices residents rely on during heat and power cuts, with the household that owns them." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Sensitive equipment', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $isFiltered ? $equipment->total().' of '.$totalEquipment : $totalEquipment }} {{ $isFiltered ? \Illuminate\Support\Str::plural('record', $equipment->total()) : \Illuminate\Support\Str::plural('record', $totalEquipment) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.equipment.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add equipment</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.equipment.index') }}" class="ha-filter" role="search" aria-label="Filter equipment">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Equipment or resident</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Search by equipment name, resident name or email">
    </div>
    <div class="ha-field">
        <label class="ha-label" for="priority-filter">Priority</label>
        <select class="ha-select" id="priority-filter" name="priority">
            <option value="">All priorities</option>
            @foreach(['high', 'medium', 'low'] as $level)<option value="{{ $level }}" @selected($filters['priority'] === $level)>{{ ucfirst($level) }}</option>@endforeach
        </select>
    </div>
    <div class="ha-field">
        <label class="ha-label" for="type-filter">Type</label>
        <select class="ha-select" id="type-filter" name="type">
            <option value="">All types</option>
            @foreach($types as $type)<option value="{{ $type }}" @selected($filters['type'] === $type)>{{ $type }}</option>@endforeach
        </select>
    </div>
    <div class="ha-filter__actions">
        <button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button>
        @if($isFiltered)<a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

@if($equipment->isEmpty())
    <div class="ha-card">
        @if($isFiltered)
            <x-ha.empty-state icon="search" title="No equipment matches these filters" description="Try a different search, priority or type.">
                <a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--outline">Reset filters</a>
            </x-ha.empty-state>
        @else
            <x-ha.empty-state icon="plug" title="No equipment yet" description="Equipment appears here once residents record it, or when you add it for a household.">
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
                    <th scope="col">Owner</th>
                    <th scope="col">Type</th>
                    <th scope="col">Priority</th>
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
                        <td><span class="ha-tag">{{ $item->type }}</span></td>
                        <td><x-ha.priority-badge :level="$item->priority_level" short /></td>
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
