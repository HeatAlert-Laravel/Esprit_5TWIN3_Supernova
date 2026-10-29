@extends('layouts.app')
@section('title', 'Weather alerts')
@section('content')
<x-ha.page-header title="Weather alerts" description="Published heat alerts for your neighborhoods — check the level, the expected maximum temperature and the advice for each one.">
    <x-slot:badges><span class="ha-count-pill">{{ $alertes->count() }} {{ $alertes->count() === 1 ? 'alert' : 'alerts' }}</span></x-slot:badges>
</x-ha.page-header>

<section class="ha-level-counters" aria-label="Alerts active today by level">
    @foreach($activeByLevel as $level => $total)
        @php $params = array_filter(['quartier_id' => $quartierId, 'q' => $search, 'niveau' => $niveau === $level ? null : $level]); @endphp
        <a href="{{ route('weather-alerts', $params) }}" class="ha-card ha-level-counter ha-level-counter--{{ $level }} @if($niveau === $level) is-selected @endif" @if($niveau === $level) aria-current="true" @endif>
            <span class="ha-level-counter__value">{{ $total }}</span>
            <span class="ha-level-counter__label">{{ \App\Models\AlerteMeteo::labelFor($level) }}</span>
            <span class="ha-level-counter__hint">active today</span>
        </a>
    @endforeach
</section>

<form method="GET" action="{{ route('weather-alerts') }}" class="ha-filter" role="search" aria-label="Filter weather alerts" data-auto-filter>
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Search</label>
        <input id="q" name="q" type="search" value="{{ $search }}" class="ha-input" placeholder="Title, neighborhood, city…">
    </div>
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="quartier_id">Neighborhood</label>
        <select id="quartier_id" name="quartier_id" class="ha-select">
            <option value="">All neighborhoods</option>
            @foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected($quartierId == $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>@endforeach
        </select>
    </div>
    <div class="ha-field">
        <label class="ha-label" for="niveau">Level</label>
        <select id="niveau" name="niveau" class="ha-select">
            <option value="">All levels</option>
            @foreach(array_reverse(\App\Models\AlerteMeteo::LEVELS) as $level)<option value="{{ $level }}" @selected($niveau === $level)>{{ \App\Models\AlerteMeteo::labelFor($level) }}</option>@endforeach
        </select>
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button></noscript>
        @if($quartierId !== '')<a href="{{ route('neighborhoods.show', $quartierId) }}" class="ha-btn ha-btn--outline">Neighborhood page</a>@endif
        @if($quartierId !== '' || $search !== '' || $niveau !== '')<a href="{{ route('weather-alerts') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

<div class="ha-grid ha-grid--2">
    @forelse($alertes as $alerte)
        @include('alertes-meteo._resident-card', ['alerte' => $alerte])
    @empty
        <div class="ha-card"><x-ha.empty-state icon="alert-triangle" title="No alerts found" description="There are no published heat alerts for this selection. When a heat alert is issued for your area, it will appear here." /></div>
    @endforelse
</div>
@endsection
