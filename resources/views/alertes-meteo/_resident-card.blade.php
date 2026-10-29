@php $linkNeighborhood = $linkNeighborhood ?? true; @endphp
<article class="ha-card ha-alert-card ha-alert-card--{{ $alerte->niveau }}" aria-label="{{ $alerte->titre }}, {{ $alerte->levelLabel() }} level, maximum {{ $alerte->temperature_max }} degrees Celsius">
    <div class="ha-alert-card__head">
        <h2 class="ha-alert-card__title">{{ $alerte->titre }}</h2>
        <x-ha.badge :variant="$alerte->levelBadgeVariant()">{{ $alerte->levelLabel() }}</x-ha.badge>
    </div>
    <p class="ha-alert-card__temp">
        <span class="ha-alert-card__temp-value">{{ number_format($alerte->temperature_max, 1) }}</span><span class="ha-alert-card__temp-unit">°C</span>
    </p>
    <p class="ha-alert-card__type">{{ $alerte->typeLabel() }}@if($alerte->temperature_min !== null) · {{ number_format($alerte->temperature_min, 0) }}° min @endif @if($alerte->temperature_ressentie !== null) · feels {{ number_format($alerte->temperature_ressentie, 0) }}° @endif</p>
    <ul class="ha-alert-card__meta">
        @if($alerte->humidite !== null || $alerte->indice_uv !== null || $alerte->vitesse_vent !== null)
            <li><x-ha.icon name="info" size="sm" /><span>@if($alerte->humidite !== null)Humidity {{ $alerte->humidite }}% @endif @if($alerte->indice_uv !== null)· UV {{ $alerte->indice_uv }} @endif @if($alerte->vitesse_vent !== null)· Wind {{ number_format($alerte->vitesse_vent, 0) }} km/h @endif</span></li>
        @endif
        <li>
            <x-ha.icon name="map-pin" size="sm" />
            @if($linkNeighborhood)
                <a href="{{ route('neighborhoods.show', $alerte->quartier) }}" class="ha-link">{{ $alerte->quartier->nom }}, {{ $alerte->quartier->ville }}</a>
            @else
                <span>{{ $alerte->quartier->nom }}, {{ $alerte->quartier->ville }}</span>
            @endif
        </li>
        <li><x-ha.icon name="clock" size="sm" /><time datetime="{{ $alerte->date_debut->toDateString() }}">{{ $alerte->date_debut->format('d/m/Y') }}</time> – <time datetime="{{ $alerte->date_fin->toDateString() }}">{{ $alerte->date_fin->format('d/m/Y') }}</time></li>
    </ul>
    <div class="ha-alert-card__foot">
        <x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ $alerte->temporalStatusLabel() }}</x-ha.badge>
        @if($alerte->risque_coupure)<x-ha.badge variant="level-red">Power outage risk</x-ha.badge>@endif
    </div>
</article>
