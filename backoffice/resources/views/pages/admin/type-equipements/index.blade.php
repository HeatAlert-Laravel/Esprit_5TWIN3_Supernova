@extends('layouts.app')
@section('title', 'Equipment types')
@section('content')
@php($isFiltered = $search !== '')
<x-ha.page-header title="Equipment types" description="The kinds of equipment residents can record, with the risk level and sensitivities each one carries." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Equipment types', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $search !== '' ? $types->total().' of '.$totalTypes : $totalTypes }} {{ \Illuminate\Support\Str::plural('type', $search !== '' ? $types->total() : $totalTypes) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.type-equipements.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add type</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.type-equipements.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="Filter equipment types">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Type name</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Search equipment types">
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary">Apply filters</button></noscript>
        @if($isFiltered)<a href="{{ route('admin.type-equipements.index') }}" class="ha-btn ha-btn--ghost">Reset filters</a>@endif
    </div>
</form>

@if($types->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="thermometer" title="No equipment types found" description="Create a type so residents can classify their equipment."><a href="{{ route('admin.type-equipements.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add type</a></x-ha.empty-state></div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Heat</th>
                    <th scope="col">Outage</th>
                    <th scope="col">Risk</th>
                    <th scope="col" class="ha-num">Equipment</th>
                    <th scope="col">Updated</th>
                    <th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($types as $type)
                    <tr>
                        <td><div class="ha-cell-person"><span class="ha-icon-chip"><x-ha.icon name="thermometer" /></span><a href="{{ route('admin.type-equipements.show', $type) }}" class="ha-cell-person__name">{{ $type->name }}</a></div></td>
                        <td><x-ha.sensitivity kind="heat" :on="$type->sensitive_to_heat" /></td>
                        <td><x-ha.sensitivity kind="outage" :on="$type->sensitive_to_outage" /></td>
                        <td><x-ha.risk-badge :level="$type->risk_level" short /></td>
                        <td class="ha-num">{{ $type->sensitive_equipments_count }}</td>
                        <td><time class="ha-mono" datetime="{{ $type->updated_at?->toDateString() }}">{{ $type->updated_at?->format('d M Y') ?? '—' }}</time></td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.type-equipements.show', $type) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="View {{ $type->name }}"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.type-equipements.edit', $type) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="Edit {{ $type->name }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                            <form method="POST" action="{{ route('admin.type-equipements.destroy', $type) }}" onsubmit="return confirm('Delete this equipment type?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="ha-btn ha-btn--danger-soft ha-btn--sm" aria-label="Delete {{ $type->name }}"><x-ha.icon name="trash" size="sm" />Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="ha-pagination">{{ $types->links() }}</div>
@endif
@endsection
