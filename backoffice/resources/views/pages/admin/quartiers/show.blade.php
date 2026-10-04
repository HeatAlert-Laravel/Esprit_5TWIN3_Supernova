@extends('layouts.app')
@section('title', 'Neighborhood details')
@section('content')
<x-ha.page-header :title="$quartier->nom" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Neighborhoods', route('admin.quartiers.index')], [$quartier->nom, null]]">
    <x-slot:actions>
        <a href="{{ route('admin.quartiers.edit', $quartier) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.quartiers.destroy', $quartier) }}" data-confirm="Delete this neighborhood and its {{ $quartier->alerteMeteos->count() }} weather alert(s)? This cannot be undone.">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <section class="ha-card" aria-labelledby="neighborhood-info">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="neighborhood-info"><span class="ha-icon-chip"><x-ha.icon name="map-pin" /></span>Neighborhood information</h2></div>
        <dl class="ha-dl ha-dl--2"><div><dt>Name</dt><dd>{{ $quartier->nom }}</dd></div><div><dt>City</dt><dd>{{ $quartier->ville }}</dd></div><div><dt>Postal code</dt><dd class="ha-mono">{{ $quartier->code_postal }}</dd></div><div><dt>Weather alerts</dt><dd><span class="ha-count-pill">{{ $quartier->alerteMeteos->count() }}</span></dd></div></dl>
    </section>

    <section class="ha-card" aria-labelledby="neighborhood-alerts">
        <div class="ha-card__head"><h2 class="ha-card__title ha-card__title--with-icon" id="neighborhood-alerts"><span class="ha-icon-chip"><x-ha.icon name="alert-triangle" /></span>Related weather alerts</h2><a href="{{ route('admin.alertes-meteo.create', ['quartier_id' => $quartier->id]) }}" class="ha-btn ha-btn--outline ha-btn--sm"><x-ha.icon name="plus" size="sm" />Add</a></div>
        @forelse($quartier->alerteMeteos as $alerte)
            <div class="ha-list-item"><div><a href="{{ route('admin.alertes-meteo.show', $alerte) }}" class="ha-cell-person__name">{{ $alerte->titre }}</a><span class="ha-cell-person__sub">{{ $alerte->date_debut->format('d/m/Y') }} - {{ $alerte->date_fin->format('d/m/Y') }}</span></div><div><x-ha.badge :variant="$alerte->levelBadgeVariant()">{{ ucfirst($alerte->niveau) }}</x-ha.badge><x-ha.badge :variant="$alerte->temporalStatusBadgeVariant()">{{ ucfirst($alerte->temporalStatus()) }}</x-ha.badge></div></div>
        @empty
            <x-ha.empty-state icon="alert-triangle" title="No weather alerts" description="This neighborhood has no alerts yet." />
        @endforelse
    </section>
</div>
@endsection
