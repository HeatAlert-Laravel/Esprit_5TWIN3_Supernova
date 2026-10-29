@extends('layouts.app')
@section('title', 'Weather alerts')
@section('content')
@php
    use App\Models\AlerteMeteo;
    $topLevel = $topAlert?->niveau ?? 'vert';
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<section class="wx-hero wx-hero--{{ $topLevel }}" aria-labelledby="wx-title">
    <div class="wx-hero__text">
        <p class="ha-eyebrow">Heat watch · updated today</p>
        <h1 id="wx-title">Weather alerts</h1>
        @if($activeTotal === 0)
            <p class="wx-hero__summary"><strong>All clear.</strong> No heat alert is active for this selection today.</p>
        @else
            <p class="wx-hero__summary"><strong>{{ $activeTotal }} {{ $activeTotal === 1 ? 'alert is' : 'alerts are' }} active today.</strong> Highest level: {{ AlerteMeteo::labelFor($topLevel) }} in {{ $topAlert->quartier->nom }}.</p>
        @endif
        <p class="wx-hero__hint">Pick a neighborhood on the map to see its level, its alerts and the live forecast.</p>
    </div>

    <div class="wx-counters" role="group" aria-label="Alerts active today by level">
        @foreach($activeByLevel as $level => $total)
            @php $params = array_filter(['quartier_id' => $quartierId, 'q' => $search, 'periode' => $periode, 'niveau' => $niveau === $level ? null : $level]); @endphp
            <a href="{{ route('weather-alerts', $params) }}" class="wx-counter wx-counter--{{ $level }} @if($niveau === $level) is-selected @endif" @if($niveau === $level) aria-current="true" @endif title="Show {{ strtolower(AlerteMeteo::labelFor($level)) }} alerts">
                <span class="wx-counter__value">{{ $total }}</span>
                <span class="wx-counter__label">{{ AlerteMeteo::labelFor($level) }}</span>
            </a>
        @endforeach
    </div>
</section>

<form method="GET" action="{{ route('weather-alerts') }}" class="ha-filter wx-filter" role="search" aria-label="Filter weather alerts" data-auto-filter>
    @if($periode !== '')<input type="hidden" name="periode" value="{{ $periode }}">@endif
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
            @foreach(array_reverse(AlerteMeteo::LEVELS) as $level)<option value="{{ $level }}" @selected($niveau === $level)>{{ AlerteMeteo::labelFor($level) }}</option>@endforeach
        </select>
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button></noscript>
        @if($quartierId !== '')<a href="{{ route('neighborhoods.show', $quartierId) }}" class="ha-btn ha-btn--outline">Neighborhood page</a>@endif
        @if($quartierId !== '' || $search !== '' || $niveau !== '' || $periode !== '')<a href="{{ route('weather-alerts') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

<section class="wx-explorer" aria-label="Map of neighborhoods" data-wx-map-section>
    <div class="wx-map-wrap">
        <div class="wx-map" data-wx-map data-points="{{ json_encode($mapQuartiers) }}" role="application" aria-label="Interactive map of neighborhoods colored by vigilance level"></div>
        <p class="wx-map__fallback" data-wx-map-fallback hidden>The map could not be loaded. You can still use the list.</p>
        <div class="wx-map__tools">
            <button type="button" class="ha-btn ha-btn--outline ha-btn--sm" data-wx-locate><x-ha.icon name="map-pin" size="sm" />Near me</button>
            <button type="button" class="ha-btn ha-btn--ghost ha-btn--sm" data-wx-reset>Show all</button>
        </div>
        <ul class="wx-legend" aria-label="Map legend">
            @foreach(AlerteMeteo::LEVELS as $level)<li><span class="wx-dot wx-dot--{{ $level }}"></span>{{ AlerteMeteo::labelFor($level) }}</li>@endforeach
        </ul>
    </div>

    <aside class="wx-places" aria-label="Neighborhoods">
        <h2 class="wx-places__title">Neighborhoods <span class="ha-count-pill">{{ $mapQuartiers->count() }}</span></h2>
        <p class="wx-places__status" data-wx-status role="status" hidden></p>
        <ul class="wx-places__list" data-wx-list>
            @forelse($mapQuartiers as $point)
                <li>
                    <button type="button" class="wx-place wx-place--{{ $point['niveau'] }}" data-wx-focus="{{ $point['id'] }}">
                        <span class="wx-dot wx-dot--{{ $point['niveau'] }}" aria-hidden="true"></span>
                        <span class="wx-place__name">{{ $point['nom'] }}<small>{{ $point['ville'] }}</small></span>
                        <span class="wx-place__meta">
                            @if($point['temperature'] !== null)<strong>{{ number_format($point['temperature'], 0) }}°</strong>@endif
                            <span>{{ $point['active'] > 0 ? $point['label'] : 'Calm' }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="wx-places__empty">No neighborhood matches this selection.</li>
            @endforelse
        </ul>
    </aside>
</section>

<div class="wx-list-head">
    <h2 class="wx-section-title">Alerts <span class="ha-count-pill">{{ $alertes->total() }}</span></h2>
    <nav class="wx-periods" aria-label="Filter alerts by period">
        @foreach(['' => 'All', 'current' => 'Active now', 'upcoming' => 'Upcoming', 'past' => 'Ended'] as $key => $label)
            <a href="{{ route('weather-alerts', array_filter(['quartier_id' => $quartierId, 'q' => $search, 'niveau' => $niveau, 'periode' => $key])) }}" class="wx-period @if($periode === $key) is-selected @endif" @if($periode === $key) aria-current="true" @endif>{{ $label }} <span>{{ $periodCounts[$key] }}</span></a>
        @endforeach
    </nav>
</div>
<div class="wx-grid">
    @forelse($alertes as $alerte)
        @include('alertes-meteo._resident-card', ['alerte' => $alerte])
    @empty
        <div class="ha-card wx-grid__empty"><x-ha.empty-state icon="alert-triangle" title="No alerts found" description="There are no published heat alerts for this selection. When a heat alert is issued for your area, it will appear here." /></div>
    @endforelse
</div>
@if($alertes->hasPages())<div class="ha-pagination">{{ $alertes->links() }}</div>@endif
@endsection
