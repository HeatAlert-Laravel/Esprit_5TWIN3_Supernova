@extends('layouts.app')
@section('title', 'Weather alerts')
@section('content')
<x-ha.page-header title="Weather alerts" description="Manage heat alerts published for residents." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Weather alerts', null]]">
	<x-slot:badges><span class="ha-count-pill">{{ $totalAlertes }} {{ \Illuminate\Support\Str::plural('alert', $totalAlertes) }}</span></x-slot:badges>
	<x-slot:actions><a href="{{ route('admin.alertes-meteo.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add alert</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.alertes-meteo.index') }}" class="ha-filter">
	<div class="ha-field ha-field--grow">
		<label class="ha-label" for="quartier_id">Neighborhood</label>
		<select id="quartier_id" name="quartier_id" class="ha-select">
			<option value="">All neighborhoods</option>
			@foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected($quartierId == $quartier->id)>{{ $quartier->nom }}</option>@endforeach
		</select>
	</div>
	<div class="ha-filter__actions">
		<button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button>
		@if($quartierId !== '')<a href="{{ route('admin.alertes-meteo.index') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
	</div>
</form>

<div class="ha-table-wrap">
	<table class="ha-table">
		<thead><tr><th>Alert</th><th>Neighborhood</th><th>Level</th><th>Temperature</th><th>Dates</th><th>Publication</th><th>Period</th><th class="ha-actions-cell"><span class="sr-only">Actions</span></th></tr></thead>
		<tbody>
			@forelse($alertes as $alerte)
				@include('pages.admin.alertes-meteo._row', ['alerte' => $alerte])
			@empty
				<tr><td colspan="8"><x-ha.empty-state icon="alert-triangle" title="No weather alerts" description="Create an alert for a neighborhood." /></td></tr>
			@endforelse
		</tbody>
	</table>
</div>
<div class="ha-pagination">{{ $alertes->links() }}</div>
@endsection
