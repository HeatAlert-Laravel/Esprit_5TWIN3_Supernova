@php $linkNeighborhood = $linkNeighborhood ?? true; @endphp
<article class="wx-card wx-card--{{ $alerte->niveau }}" aria-label="{{ $alerte->titre }}, {{ $alerte->levelLabel() }} level, maximum {{ $alerte->temperature_max }} degrees Celsius">
    <header class="wx-card__top">
        <span class="wx-card__level"><span class="wx-dot wx-dot--{{ $alerte->niveau }}" aria-hidden="true"></span>{{ $alerte->levelLabel() }} · {{ $alerte->typeLabel() }}</span>
        <x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ $alerte->temporalStatusLabel() }}</x-ha.badge>
    </header>

    <div class="wx-card__body">
        <div>
            <h2 class="wx-card__title"><a href="{{ route('weather-alerts.show', $alerte) }}" class="wx-card__link">{{ $alerte->titre }}</a></h2>
            <p class="wx-card__place">
                <x-ha.icon name="map-pin" size="sm" />
                @if($linkNeighborhood)
                    <a href="{{ route('neighborhoods.show', $alerte->quartier) }}" class="ha-link">{{ $alerte->quartier->nom }}, {{ $alerte->quartier->ville }}</a>
                @else
                    <span>{{ $alerte->quartier->nom }}, {{ $alerte->quartier->ville }}</span>
                @endif
            </p>
        </div>
        <p class="wx-card__temp" aria-hidden="true"><span>{{ number_format($alerte->temperature_max, 0) }}</span><sup>°C</sup></p>
    </div>

    <ul class="wx-chips" aria-label="Weather details">
        @if($alerte->temperature_min !== null)<li><x-ha.icon name="thermometer" size="sm" />{{ number_format($alerte->temperature_min, 0) }}° min</li>@endif
        @if($alerte->temperature_ressentie !== null)<li>Feels {{ number_format($alerte->temperature_ressentie, 0) }}°</li>@endif
        @if($alerte->humidite !== null)<li>Humidity {{ $alerte->humidite }}%</li>@endif
        @if($alerte->indice_uv !== null)<li><x-ha.icon name="sun" size="sm" />UV {{ $alerte->indice_uv }}</li>@endif
        @if($alerte->vitesse_vent !== null)<li>Wind {{ number_format($alerte->vitesse_vent, 0) }} km/h</li>@endif
    </ul>

    <footer class="wx-card__foot">
        <span class="wx-card__dates"><x-ha.icon name="clock" size="sm" /><time datetime="{{ $alerte->date_debut->toDateString() }}">{{ $alerte->date_debut->format('d/m/Y') }}</time> – <time datetime="{{ $alerte->date_fin->toDateString() }}">{{ $alerte->date_fin->format('d/m/Y') }}</time></span>
        @if($alerte->risque_coupure)<x-ha.badge variant="level-red"><x-ha.icon name="zap" size="sm" />Power outage risk</x-ha.badge>@endif
    </footer>
    <p class="wx-card__advice">{{ $alerte->levelAdvice() }}</p>
</article>
