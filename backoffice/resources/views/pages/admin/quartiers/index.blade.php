@extends('layouts.app')
@section('title', 'Neighborhoods')
@section('content')
<x-ha.page-header title="Neighborhoods" description="Manage the areas used by HeatAlert alerts and community services." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Neighborhoods', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $search !== '' ? $quartiers->total().' of '.$totalQuartiers : $totalQuartiers }} {{ \Illuminate\Support\Str::plural('record', $search !== '' ? $quartiers->total() : $totalQuartiers) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.quartiers.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add neighborhood</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.quartiers.index') }}" class="ha-filter" role="search" aria-label="Filter neighborhoods">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Neighborhood, city or postal code</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Search neighborhoods">
    </div>
    <div class="ha-filter__actions">
        <button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button>
        @if($search !== '')<a href="{{ route('admin.quartiers.index') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

@if($quartiers->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="map-pin" title="No neighborhoods found" description="Create a neighborhood to prepare the weather alerts module." /></div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead><tr><th scope="col">Neighborhood</th><th scope="col">City</th><th scope="col">Postal code</th><th scope="col">Weather alerts</th><th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @foreach($quartiers as $quartier)
                    <tr>
                        <td><div class="ha-cell-person"><span class="ha-icon-chip"><x-ha.icon name="map-pin" /></span><a href="{{ route('admin.quartiers.show', $quartier) }}" class="ha-cell-person__name">{{ $quartier->nom }}</a></div></td>
                        <td>{{ $quartier->ville }}</td>
                        <td><span class="ha-mono">{{ $quartier->code_postal }}</span></td>
                        <td><span class="ha-count-pill">{{ $quartier->alerte_meteos_count }}</span></td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.quartiers.show', $quartier) }}" class="ha-btn ha-btn--outline ha-btn--sm"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.quartiers.edit', $quartier) }}" class="ha-btn ha-btn--ghost ha-btn--sm"><x-ha.icon name="pencil" size="sm" />Edit</a>
                            <form method="POST" action="{{ route('admin.quartiers.destroy', $quartier) }}" onsubmit="return confirm('Delete this neighborhood?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="ha-btn ha-btn--danger-soft ha-btn--sm"><x-ha.icon name="trash" size="sm" />Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="ha-pagination">{{ $quartiers->links() }}</div>
@endif
@endsection
