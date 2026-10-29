@extends('layouts.app')
@section('title', $quartier->nom)
@section('content')
@php
    use App\Models\AlerteMeteo;
    $today = $weather[0] ?? null;
    $location = collect([$quartier->ville, $quartier->gouvernorat !== $quartier->ville ? $quartier->gouvernorat : null])->filter()->implode(', ');
@endphp
<x-ha.page-header :title="$quartier->nom" :description="$location.' · '.$quartier->code_postal" :breadcrumbs="[['Weather alerts', route('weather-alerts')], [$quartier->nom, null]]" />

<div class="ha-grid ha-grid--2">
    <section class="ha-card ha-alert-card ha-alert-card--{{ $level }}" aria-labelledby="current-level">
        <div class="ha-alert-card__head">
            <h2 class="ha-alert-card__title" id="current-level">Vigilance level today</h2>
            <x-ha.badge :variant="AlerteMeteo::badgeVariantFor($level)">{{ AlerteMeteo::labelFor($level) }}</x-ha.badge>
        </div>
        <p class="ha-alert-card__advice">
            @if($current->isEmpty())
                No heat alert is active for this neighborhood today.
            @else
                {{ $current->count() }} {{ $current->count() === 1 ? 'alert is' : 'alerts are' }} active today. {{ AlerteMeteo::adviceFor($level) }}
            @endif
        </p>
        @if($current->contains('risque_coupure', true))
            <div class="ha-alert-card__foot"><x-ha.badge variant="level-red">Power outage risk</x-ha.badge></div>
        @endif
    </section>

    <section class="ha-card" aria-labelledby="live-weather">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="live-weather"><span class="ha-icon-chip"><x-ha.icon name="sun" /></span>Live weather</h2></div>
        @if($today)
            <p class="ha-weather-now">
                <span class="ha-weather-now__value">{{ number_format($today['temperature_max'], 0) }}°C</span>
                <span class="ha-weather-now__meta">max today · feels like {{ number_format($today['temperature_ressentie'], 0) }}° · {{ $today['humidite'] }}% humidity · UV {{ $today['indice_uv'] }} · wind {{ number_format($today['vitesse_vent'], 0) }} km/h</span>
            </p>
            <ul class="ha-weather-days">
                @foreach($weather as $day)
                    <li>
                        <time datetime="{{ $day['date'] }}">{{ \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('D d M') }}</time>
                        <span>{{ number_format($day['temperature_min'], 0) }}° / {{ number_format($day['temperature_max'], 0) }}°</span>
                        <x-ha.badge :variant="AlerteMeteo::badgeVariantFor($day['niveau'])">{{ AlerteMeteo::labelFor($day['niveau']) }}</x-ha.badge>
                    </li>
                @endforeach
            </ul>
            <p class="ha-help">Forecast by Open-Meteo. The level shown for each day is an estimate from the expected maximum temperature, not an official alert.</p>
        @elseif($weatherError)
            <x-ha.empty-state icon="alert-triangle" title="Forecast unavailable" :description="$weatherError" />
        @else
            <x-ha.empty-state icon="map-pin" title="No forecast yet" description="This neighborhood has no coordinates, so the live forecast cannot be shown." />
        @endif
    </section>
</div>

@foreach([['Active today', $current, 'No active alert.'], ['Upcoming', $upcoming, 'No upcoming alert.'], ['Recently ended', $past, 'No past alert.']] as [$heading, $group, $emptyText])
    <h2 class="ha-section-title">{{ $heading }} <span class="ha-count-pill">{{ $group->count() }}</span></h2>
    <div class="ha-grid ha-grid--2">
        @forelse($group as $alerte)
            @include('alertes-meteo._resident-card', ['alerte' => $alerte, 'linkNeighborhood' => false])
        @empty
            <div class="ha-card"><p class="ha-help">{{ $emptyText }}</p></div>
        @endforelse
    </div>
@endforeach
@endsection
