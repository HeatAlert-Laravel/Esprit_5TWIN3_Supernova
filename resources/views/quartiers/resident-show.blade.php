@extends('layouts.app')
@section('title', $quartier->nom)
@section('content')
@php
    use App\Models\AlerteMeteo;
    $today = $weather['daily'][0] ?? null;
    $now = $weather['current'] ?? null;
    $location = collect([$quartier->ville, $quartier->gouvernorat !== $quartier->ville ? $quartier->gouvernorat : null])->filter()->implode(', ');
    $outage = $current->contains('risque_coupure', true);
    $tabs = [
        ['key' => 'current', 'label' => 'Active today', 'items' => $current, 'empty' => 'No active alert.'],
        ['key' => 'upcoming', 'label' => 'Upcoming', 'items' => $upcoming, 'empty' => 'No upcoming alert.'],
        ['key' => 'past', 'label' => 'Recently ended', 'items' => $past, 'empty' => 'No past alert.'],
    ];
    $defaultTab = collect($tabs)->first(fn ($tab) => $tab['items']->isNotEmpty())['key'] ?? 'current';
    $point = $quartier->hasCoordinates() ? [['id' => $quartier->id, 'nom' => $quartier->nom, 'ville' => $quartier->ville, 'lat' => $quartier->latitude, 'lng' => $quartier->longitude, 'niveau' => $level, 'label' => AlerteMeteo::labelFor($level), 'active' => $current->count(), 'temperature' => $current->first()?->temperature_max, 'title' => $current->first()?->titre, 'outage' => $outage, 'url' => null]] : [];
@endphp
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<nav aria-label="Breadcrumb" class="wx-back"><a href="{{ route('weather-alerts') }}" class="ha-link"><x-ha.icon name="arrow-left" size="sm" />All weather alerts</a></nav>

<section class="wx-banner wx-banner--{{ $level }}" aria-labelledby="nb-title">
    <div class="wx-banner__main">
        <p class="ha-eyebrow">{{ $location }} · {{ $quartier->code_postal }}</p>
        <h1 id="nb-title">{{ $quartier->nom }}</h1>
        <div class="wx-banner__level">
            <span class="wx-banner__pill"><span class="wx-dot wx-dot--{{ $level }}" aria-hidden="true"></span>{{ AlerteMeteo::labelFor($level) }} vigilance</span>
            @if($outage)<x-ha.badge variant="level-red"><x-ha.icon name="zap" size="sm" />Power outage risk</x-ha.badge>@endif
        </div>
        <p class="wx-banner__advice">
            @if($current->isEmpty())
                No heat alert is active for this neighborhood today.
            @else
                {{ $current->count() }} {{ $current->count() === 1 ? 'alert is' : 'alerts are' }} active today. {{ AlerteMeteo::adviceFor($level) }}
            @endif
        </p>

        @if($today)
            <dl class="wx-banner__stats" aria-label="Weather today">
                <div><dt>Max today</dt><dd>{{ number_format($today['temperature_max'], 0) }}°C</dd></div>
                <div><dt>Feels like</dt><dd>{{ number_format($today['temperature_ressentie'], 0) }}°C</dd></div>
                <div><dt>Humidity</dt><dd>{{ $today['humidite'] }}%</dd></div>
                <div><dt>UV index</dt><dd>{{ $today['indice_uv'] }}</dd></div>
                <div><dt>Wind</dt><dd>{{ number_format($today['vitesse_vent'], 0) }} km/h</dd></div>
            </dl>
        @endif
    </div>

    <div class="wx-banner__map">
        @if($point)
            <div class="wx-map wx-map--mini" data-wx-map data-mini data-points="{{ json_encode($point) }}" role="img" aria-label="Map showing {{ $quartier->nom }}"></div>
            <p class="wx-map__fallback" data-wx-map-fallback hidden>The map could not be loaded.</p>
        @else
            <div class="wx-map wx-map--empty"><x-ha.icon name="map-pin" /><span>No coordinates yet</span></div>
        @endif
    </div>
</section>

<section class="wx-forecast" aria-labelledby="live-weather">
    <div class="wx-section-head">
        <h2 id="live-weather">Live weather</h2>
        <span class="wx-source">Open-Meteo</span>
    </div>
    @if($weather)
        @php
            $daily = $weather['daily'];
            $hourly = $weather['hourly'];
            $weekMin = min(array_column($daily, 'temperature_min'));
            $weekMax = max(array_column($daily, 'temperature_max'));
            $span = max(1, $weekMax - $weekMin);
        @endphp

        @if($now)
            <div class="wx-now wx-now--{{ $now['condition']['kind'] }} @if(! $now['is_day']) wx-now--night @endif">
                <div class="wx-now__main">
                    <x-ha.weather-icon :kind="$now['condition']['kind']" :day="$now['is_day']" :size="92" />
                    <div>
                        <p class="wx-now__temp">{{ number_format($now['temperature'], 0) }}<small>°C</small></p>
                        <p class="wx-now__cond">{{ $now['condition']['label'] }}</p>
                        <p class="wx-now__feels">Feels like {{ number_format($now['ressentie'], 0) }}° · H {{ number_format($today['temperature_max'], 0) }}° L {{ number_format($today['temperature_min'], 0) }}°</p>
                    </div>
                </div>
                <dl class="wx-now__facts" aria-label="Current conditions">
                    <div><dt>Humidity</dt><dd>{{ $now['humidite'] }}%</dd></div>
                    <div><dt>Wind</dt><dd>{{ number_format($now['vent'], 0) }} km/h</dd></div>
                    <div><dt>UV index</dt><dd>{{ $today['indice_uv'] }}</dd></div>
                    <div><dt>Rain chance</dt><dd>{{ $today['pluie'] !== null ? $today['pluie'].'%' : '—' }}</dd></div>
                    <div><dt>Sunrise</dt><dd>{{ $today['lever'] ?? '—' }}</dd></div>
                    <div><dt>Sunset</dt><dd>{{ $today['coucher'] ?? '—' }}</dd></div>
                </dl>
            </div>
        @endif

        @if($hourly)
            <h3 class="wx-sub">Next 24 hours</h3>
            <ol class="wx-hourly" aria-label="Hourly forecast" tabindex="0">
                @foreach($hourly as $hour)
                    <li class="wx-hour @if($loop->first) is-now @endif">
                        <span class="wx-hour__time">{{ $loop->first ? 'Now' : $hour['hour'] }}</span>
                        <x-ha.weather-icon :kind="$hour['condition']['kind']" :day="$hour['is_day']" :size="36" />
                        <strong class="wx-hour__temp">{{ $hour['temperature'] }}°</strong>
                        <span class="wx-hour__rain" title="Chance of rain">{{ ($hour['pluie'] ?? 0) > 0 ? $hour['pluie'].'%' : '' }}</span>
                    </li>
                @endforeach
            </ol>
        @endif

        <h3 class="wx-sub">{{ count($daily) }}-day forecast</h3>
        <ol class="wx-week">
            @foreach($daily as $day)
                @php
                    $left = (($day['temperature_min'] - $weekMin) / $span) * 100;
                    $width = max(8, (($day['temperature_max'] - $day['temperature_min']) / $span) * 100);
                @endphp
                <li class="wx-weekday wx-weekday--{{ $day['niveau'] }} @if($loop->first) is-today @endif">
                    <time datetime="{{ $day['date'] }}" class="wx-weekday__name">{{ $loop->first ? 'Today' : \Illuminate\Support\Carbon::parse($day['date'])->translatedFormat('l') }}</time>
                    <span class="wx-weekday__cond"><x-ha.weather-icon :kind="$day['condition']['kind']" :size="30" /><span>{{ $day['condition']['label'] }}</span></span>
                    <span class="wx-weekday__rain" title="Chance of rain">{{ ($day['pluie'] ?? 0) > 0 ? $day['pluie'].'%' : '' }}</span>
                    <span class="wx-weekday__min">{{ number_format($day['temperature_min'], 0) }}°</span>
                    <span class="wx-weekday__track" aria-hidden="true"><span style="left: {{ round($left) }}%; width: {{ round(min($width, 100 - $left)) }}%"></span></span>
                    <strong class="wx-weekday__max">{{ number_format($day['temperature_max'], 0) }}°</strong>
                    <x-ha.badge :variant="AlerteMeteo::badgeVariantFor($day['niveau'])">{{ AlerteMeteo::labelFor($day['niveau']) }}</x-ha.badge>
                </li>
            @endforeach
        </ol>
        <p class="ha-help">Forecast by Open-Meteo. The level shown for each day is an estimate from the expected maximum temperature, not an official alert.</p>
    @elseif($weatherError)
        <div class="ha-card"><x-ha.empty-state icon="alert-triangle" title="Forecast unavailable" :description="$weatherError" /></div>
    @else
        <div class="ha-card"><x-ha.empty-state icon="map-pin" title="No forecast yet" description="This neighborhood has no coordinates, so the live forecast cannot be shown." /></div>
    @endif
</section>

<section class="wx-alerts" aria-labelledby="nb-alerts" data-wx-tabs data-default-tab="{{ $defaultTab }}">
    <div class="wx-section-head"><h2 id="nb-alerts">Alerts</h2></div>
    <div class="wx-tabs" role="tablist" aria-label="Alerts by period">
        @foreach($tabs as $tab)
            <button type="button" role="tab" class="wx-tab" id="tab-{{ $tab['key'] }}" data-wx-tab="{{ $tab['key'] }}" aria-controls="panel-{{ $tab['key'] }}" aria-selected="{{ $tab['key'] === $defaultTab ? 'true' : 'false' }}">{{ $tab['label'] }} <span class="ha-count-pill">{{ $tab['items']->count() }}</span></button>
        @endforeach
    </div>
    @foreach($tabs as $tab)
        <div class="wx-panel" role="tabpanel" id="panel-{{ $tab['key'] }}" aria-labelledby="tab-{{ $tab['key'] }}" data-wx-panel="{{ $tab['key'] }}">
            <h3 class="wx-panel__heading">{{ $tab['label'] }}</h3>
            <div class="wx-grid">
                @forelse($tab['items'] as $alerte)
                    @include('alertes-meteo._resident-card', ['alerte' => $alerte, 'linkNeighborhood' => false])
                @empty
                    <div class="ha-card wx-grid__empty"><p class="ha-help">{{ $tab['empty'] }}</p></div>
                @endforelse
            </div>
        </div>
    @endforeach
</section>
@endsection
