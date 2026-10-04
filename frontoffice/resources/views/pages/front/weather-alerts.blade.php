@extends('layouts.front')
@section('title', 'Weather alerts')
@section('content')
<x-ha.page-header title="Weather alerts" description="Published heat alerts for your neighborhoods — check the level, the expected maximum temperature and the advice for each one.">
    <x-slot:badges><span class="ha-count-pill">{{ $alertes->count() }} {{ $alertes->count() === 1 ? 'alert' : 'alerts' }}</span></x-slot:badges>
</x-ha.page-header>

<form method="GET" action="{{ route('weather-alerts') }}" class="ha-filter" role="search" aria-label="Filter weather alerts" data-auto-filter>
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="quartier_id">Neighborhood</label>
        <select id="quartier_id" name="quartier_id" class="ha-select">
            <option value="">All neighborhoods</option>
            @foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected($quartierId == $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>@endforeach
        </select>
    </div>
    <div class="ha-filter__actions">
        <noscript><button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button></noscript>
        @if($quartierId !== '')<a href="{{ route('weather-alerts') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

<div class="ha-grid ha-grid--2">
    @forelse($alertes as $alerte)
        <article class="ha-card ha-alert-card ha-alert-card--{{ $alerte->niveau }}" aria-label="{{ $alerte->titre }}, {{ $alerte->levelLabel() }} level, maximum {{ $alerte->temperature_max }} degrees Celsius">
            <div class="ha-alert-card__head">
                <h2 class="ha-alert-card__title">{{ $alerte->titre }}</h2>
                <x-ha.badge :variant="$alerte->levelBadgeVariant()">{{ $alerte->levelLabel() }}</x-ha.badge>
            </div>
            <p class="ha-alert-card__temp">
                <span class="ha-alert-card__temp-value">{{ number_format($alerte->temperature_max, 1) }}</span><span class="ha-alert-card__temp-unit">°C</span>
              <!--   <span class="ha-alert-card__temp-label">forecast maximum</span> -->
            </p>
            <!-- <p class="ha-alert-card__advice">{{ $alerte->levelAdvice() }}</p> -->
            <ul class="ha-alert-card__meta">
                <li><x-ha.icon name="map-pin" size="sm" /><span>{{ $alerte->quartier->nom }}, {{ $alerte->quartier->ville }}</span></li>
                <li><x-ha.icon name="clock" size="sm" /><time datetime="{{ $alerte->date_debut->toDateString() }}">{{ $alerte->date_debut->format('d/m/Y') }}</time> – <time datetime="{{ $alerte->date_fin->toDateString() }}">{{ $alerte->date_fin->format('d/m/Y') }}</time></li>
            </ul>
            <div class="ha-alert-card__foot">
                <x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ $alerte->temporalStatusLabel() }}</x-ha.badge>
            </div>
        </article>
    @empty
        <div class="ha-card"><x-ha.empty-state icon="alert-triangle" title="No active alerts" description="There are no published heat alerts for this selection. When a heat alert is issued for your area, it will appear here." /></div>
    @endforelse
</div>
@endsection