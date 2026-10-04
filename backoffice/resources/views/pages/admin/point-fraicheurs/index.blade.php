@extends('layouts.app')
@section('title', 'Cooling points')
@section('content')
@php($isFiltered = $search !== '' || $selectedType !== null || $selectedQuartier !== null || $selectedStatus !== null)
<x-ha.page-header title="Cooling points" description="Management of refuge locations, shaded parks and misting spots for residents." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Cooling points', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $isFiltered ? $points->total().' of '.$totalPoints : $totalPoints }} {{ \Illuminate\Support\Str::plural('point', $isFiltered ? $points->total() : $totalPoints) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.point-fraicheurs.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add cooling point</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.point-fraicheurs.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="Filter cooling points">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Search</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Search by name or address...">
    </div>

    <div class="ha-field">
        <label class="ha-label" for="type_id">Point type</label>
        <select id="type_id" name="type_id" class="ha-select">
            <option value="">All types</option>
            @foreach($types as $type)
                <option value="{{ $type->id }}" @selected((string)$selectedType === (string)$type->id)>{{ $type->nom }}</option>
            @endforeach
        </select>
    </div>

    <div class="ha-field">
        <label class="ha-label" for="quartier_id">Neighborhood</label>
        <select id="quartier_id" name="quartier_id" class="ha-select">
            <option value="">All neighborhoods</option>
            @foreach($quartiers as $quartier)
                <option value="{{ $quartier->id }}" @selected((string)$selectedQuartier === (string)$quartier->id)>{{ $quartier->nom }}</option>
            @endforeach
        </select>
    </div>

    <div class="ha-field">
        <label class="ha-label" for="status">Status</label>
        <select id="status" name="status" class="ha-select">
            <option value="">All statuses</option>
            <option value="active" @selected($selectedStatus === 'active')>Active</option>
            <option value="inactive" @selected($selectedStatus === 'inactive')>Inactive</option>
        </select>
    </div>

    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary">Filter</button></noscript>
        @if($isFiltered)<a href="{{ route('admin.point-fraicheurs.index') }}" class="ha-btn ha-btn--ghost">Reset filters</a>@endif
    </div>
</form>

@if($points->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="snowflake" title="No cooling points found" description="Adjust your filters or add a new cooling location."><a href="{{ route('admin.point-fraicheurs.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add point</a></x-ha.empty-state></div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead>
                <tr>
                    <th scope="col">Location</th>
                    <th scope="col">Type</th>
                    <th scope="col">Neighborhood</th>
                    <th scope="col">Hours</th>
                    <th scope="col">PRM</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($points as $point)
                    <tr>
                        <td>
                            <div class="ha-cell-person">
                                <span class="ha-icon-chip"><x-cooling-icon :name="$point->typePoint?->icone ?? 'snowflake'" /></span>
                                <div>
                                    <a href="{{ route('admin.point-fraicheurs.show', $point) }}" class="ha-cell-person__name font-medium">{{ $point->nom }}</a>
                                    <p class="text-xs text-gray-500">{{ $point->adresse }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($point->typePoint)
                                <a href="{{ route('admin.type-points.show', $point->typePoint) }}" class="ha-badge ha-badge--neutral hover:underline inline-flex items-center gap-1">
                                    <x-cooling-icon :name="$point->typePoint->icone ?? 'snowflake'" size="sm" />
                                    {{ $point->typePoint->nom }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            @if($point->quartier)
                                <span class="text-sm font-medium">{{ $point->quartier->nom }}</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td><span class="ha-mono text-xs">{{ $point->horaires ?? '—' }}</span></td>
                        <td>
                            @if($point->accessible_pmr)
                                <span class="ha-badge ha-badge--success" title="PRM accessible">PRM</span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.point-fraicheurs.toggle-status', $point) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="ha-badge {{ $point->actif ? 'ha-badge--success' : 'ha-badge--danger' }} cursor-pointer hover:opacity-80" title="Click to toggle status">
                                    {{ $point->actif ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.point-fraicheurs.show', $point) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="View {{ $point->nom }}"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.point-fraicheurs.edit', $point) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="Edit {{ $point->nom }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                            <form method="POST" action="{{ route('admin.point-fraicheurs.destroy', $point) }}" onsubmit="return confirm('Delete this cooling point?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="ha-btn ha-btn--danger-soft ha-btn--sm" aria-label="Delete {{ $point->nom }}"><x-ha.icon name="trash" size="sm" />Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="ha-pagination">{{ $points->links() }}</div>
@endif
@endsection
