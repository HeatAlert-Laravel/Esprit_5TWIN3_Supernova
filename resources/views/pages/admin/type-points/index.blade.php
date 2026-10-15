@extends('layouts.app')
@section('title', 'Point types')
@section('content')
@php($isFiltered = $search !== '')
<x-ha.page-header title="Point types" description="Categories of places where residents can seek relief from heat." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Point types', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $search !== '' ? $types->total().' of '.$totalTypes : $totalTypes }} {{ \Illuminate\Support\Str::plural('type', $search !== '' ? $types->total() : $totalTypes) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.type-points.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add type</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.type-points.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="Filter point types">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Search</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Search by name...">
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary">Filter</button></noscript>
        @if($isFiltered)<a href="{{ route('admin.type-points.index') }}" class="ha-btn ha-btn--ghost">Reset filters</a>@endif
    </div>
</form>

@if($types->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="sun" title="No point types found" description="Create a first type to categorize cooling locations."><a href="{{ route('admin.type-points.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add type</a></x-ha.empty-state></div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead>
                <tr>
                    <th scope="col">Type name</th>
                    <th scope="col">Icon</th>
                    <th scope="col">Description</th>
                    <th scope="col" class="ha-num">Cooling points</th>
                    <th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($types as $type)
                    <tr>
                        <td>
                            <div class="ha-cell-person">
                                <span class="ha-icon-chip"><x-cooling-icon :name="$type->icone ?? 'sun'" /></span>
                                <a href="{{ route('admin.type-points.show', $type) }}" class="ha-cell-person__name font-medium">{{ $type->nom }}</a>
                            </div>
                        </td>
                        <td>
                            @if($type->icone)
                                <span class="ha-badge ha-badge--neutral flex items-center gap-1 w-fit"><x-cooling-icon :name="$type->icone" size="sm" />{{ $type->icone }}</span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                        <td class="text-sm text-gray-500 truncate max-w-xs">{{ $type->description ?? '—' }}</td>
                        <td class="ha-num">{{ $type->point_fraicheurs_count }}</td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.type-points.show', $type) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="View {{ $type->nom }}"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.type-points.edit', $type) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="Edit {{ $type->nom }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                            <form method="POST" action="{{ route('admin.type-points.destroy', $type) }}" onsubmit="return confirm('Delete this point type?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="ha-btn ha-btn--danger-soft ha-btn--sm" aria-label="Delete {{ $type->nom }}"><x-ha.icon name="trash" size="sm" />Delete</button>
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
