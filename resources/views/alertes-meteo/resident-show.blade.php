@extends('layouts.app')
@section('title', $alerte->titre)
@section('content')
@php
    use App\Models\AlerteMeteo;
    use Illuminate\Support\Str;
    $level = $alerte->niveau;
@endphp
<nav aria-label="Breadcrumb" class="wx-back">
    <a href="{{ route('weather-alerts') }}" class="ha-link"><x-ha.icon name="arrow-left" size="sm" />All weather alerts</a>
</nav>

<section class="wx-banner wx-banner--{{ $level }}" aria-labelledby="alert-title">
    <div class="wx-banner__main">
        <p class="ha-eyebrow">{{ $alerte->typeLabel() }} · <a href="{{ route('neighborhoods.show', $quartier) }}" class="ha-link">{{ $quartier->nom }}, {{ $quartier->ville }}</a></p>
        <h1 id="alert-title">{{ $alerte->titre }}</h1>
        <div class="wx-banner__level">
            <span class="wx-banner__pill"><span class="wx-dot wx-dot--{{ $level }}" aria-hidden="true"></span>{{ AlerteMeteo::labelFor($level) }} level</span>
            <x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ $alerte->temporalStatusLabel() }}</x-ha.badge>
            @if($alerte->risque_coupure)<x-ha.badge variant="level-red"><x-ha.icon name="zap" size="sm" />Power outage risk</x-ha.badge>@endif
        </div>
        <p class="wx-banner__advice">{{ $alerte->levelAdvice() }}</p>
        <p class="wx-detail__dates"><x-ha.icon name="clock" size="sm" /><time datetime="{{ $alerte->date_debut->toDateString() }}">{{ $alerte->date_debut->format('d/m/Y') }}</time> – <time datetime="{{ $alerte->date_fin->toDateString() }}">{{ $alerte->date_fin->format('d/m/Y') }}</time></p>

        <dl class="wx-banner__stats" aria-label="Forecast details">
            <div><dt>Minimum</dt><dd>{{ $alerte->temperature_min !== null ? number_format($alerte->temperature_min, 0).'°C' : '—' }}</dd></div>
            <div><dt>Feels like</dt><dd>{{ $alerte->temperature_ressentie !== null ? number_format($alerte->temperature_ressentie, 0).'°C' : '—' }}</dd></div>
            <div><dt>Humidity</dt><dd>{{ $alerte->humidite !== null ? $alerte->humidite.'%' : '—' }}</dd></div>
            <div><dt>UV index</dt><dd>{{ $alerte->indice_uv ?? '—' }}</dd></div>
            <div><dt>Wind</dt><dd>{{ $alerte->vitesse_vent !== null ? number_format($alerte->vitesse_vent, 0).' km/h' : '—' }}</dd></div>
        </dl>
        @if($alerte->source)<p class="ha-help">Source: {{ $alerte->source }}</p>@endif
    </div>
    <div class="wx-bigtemp wx-bigtemp--{{ $level }}" aria-label="Maximum temperature {{ number_format($alerte->temperature_max, 0) }} degrees Celsius">
        <span class="wx-bigtemp__value">{{ number_format($alerte->temperature_max, 0) }}<small>°C</small></span>
        <span class="wx-bigtemp__label">Expected maximum</span>
    </div>
</section>

<section class="wx-resources" aria-labelledby="res-advice">
    <div class="wx-section-head">
        <h2 id="res-advice">Advice for this situation</h2>
        <a href="{{ route('advice') }}" class="ha-link">All advice</a>
    </div>
    <div class="wx-grid">
        @forelse($advice as $conseil)
            <a href="{{ route('advice.show', $conseil) }}" class="wx-res-card">
                <span class="wx-res-card__meta"><x-ha.icon :name="$conseil->situationIcon()" size="sm" />{{ $conseil->categorieConseil?->nom }} · {{ $conseil->readingMinutes() }} min read</span>
                <strong class="wx-res-card__title">{{ $conseil->titre }}</strong>
                <span class="wx-res-card__text">{{ Str::limit($conseil->resume, 110) }}</span>
                <span class="wx-res-card__foot">{{ $conseil->audienceLabel() }}<x-ha.icon name="arrow-right" size="sm" /></span>
            </a>
        @empty
            <div class="ha-card wx-grid__empty"><p class="ha-help">No advice has been published for this situation yet.</p></div>
        @endforelse
    </div>
</section>

<div class="wx-resources-pair">
    <section class="wx-resources" aria-labelledby="res-cooling">
        <div class="wx-section-head">
            <h2 id="res-cooling">Cooling points in {{ $quartier->nom }}</h2>
            <a href="{{ route('cooling-points', ['quartier_id' => $quartier->id]) }}" class="ha-link">See all ({{ $coolingPointsCount }})</a>
        </div>
        <ul class="wx-rows">
            @forelse($coolingPoints as $point)
                @php $open = $point->getOpenStatus(); @endphp
                <li class="wx-row">
                    <span class="wx-row__icon"><x-ha.icon name="snowflake" size="sm" /></span>
                    <span class="wx-row__main"><strong>{{ $point->nom }}</strong><small>{{ $point->typePoint?->nom }}@if($point->adresse) · {{ $point->adresse }}@endif</small></span>
                    <x-ha.badge :variant="$open['is_open'] ? 'success' : 'neutral'">{{ $open['label'] }}</x-ha.badge>
                </li>
            @empty
                <li class="wx-row wx-row--empty">No cooling point is listed for this neighborhood yet.</li>
            @endforelse
        </ul>
    </section>

    <section class="wx-resources" aria-labelledby="res-outages">
        <div class="wx-section-head">
            <h2 id="res-outages">Power outages</h2>
            <a href="{{ route('outages', ['quartier_id' => $quartier->id]) }}" class="ha-link">See all ({{ $outagesCount }})</a>
        </div>
        <ul class="wx-rows">
            @forelse($outages as $coupure)
                <li class="wx-row">
                    <span class="wx-row__icon wx-row__icon--warn"><x-ha.icon name="zap" size="sm" /></span>
                    <span class="wx-row__main"><strong>{{ ['prévue' => 'Planned', 'en cours' => 'Ongoing'][$coupure->type] ?? ucfirst($coupure->type) }} outage</strong><small>Since {{ $coupure->date_debut->format('d/m/Y H:i') }}@if($coupure->description) · {{ Str::limit($coupure->description, 70) }}@endif</small></span>
                </li>
            @empty
                <li class="wx-row wx-row--empty">No active outage is reported in this neighborhood.@if($alerte->risque_coupure) The heat may still overload the grid: keep your devices charged.@endif</li>
            @endforelse
        </ul>
    </section>
</div>

@if($otherAlerts->isNotEmpty())
    <section class="wx-resources" aria-labelledby="res-others">
        <div class="wx-section-head"><h2 id="res-others">Other alerts in {{ $quartier->nom }}</h2></div>
        <div class="wx-grid">
            @foreach($otherAlerts as $other)
                @include('alertes-meteo._resident-card', ['alerte' => $other, 'linkNeighborhood' => false])
            @endforeach
        </div>
    </section>
@endif
@endsection
