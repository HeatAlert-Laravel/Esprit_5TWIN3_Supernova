@extends('layouts.app')
@section('title', 'My reports')
@section('content')
@php($labels = ['en attente' => ['Pending', 'warning'], 'validé' => ['Validated', 'success'], 'rejeté' => ['Rejected', 'danger']])
<div class="my-reports">
    <section class="wx-hero my-reports__hero" aria-labelledby="my-reports-title">
        <div><p class="ha-eyebrow">HeatAlert · My activity</p><h1 id="my-reports-title">My reports</h1><p class="wx-hero__summary">View your reports and track their status.</p><p class="wx-hero__hint">Only reports submitted from your account are shown here.</p></div>
        <div class="my-reports__overview"><strong>{{ $counts->sum() }}</strong><span>report{{ $counts->sum() > 1 ? 's' : '' }} in total</span><a class="ha-btn ha-btn--primary" href="{{ route('outages.report.create') }}"><x-ha.icon name="plus" size="sm" />New report</a></div>
    </section>
    <div class="my-reports__statuses" aria-label="Statuses of all your reports">
        @foreach($labels as $statut => [$label, $variant])
            <a class="my-reports__status @if(request('statut') === $statut) is-selected @endif" href="{{ route('my-reports', array_filter(['q' => request('q'), 'sort' => request('sort'), 'statut' => request('statut') === $statut ? null : $statut])) }}" @if(request('statut') === $statut) aria-current="true" @endif><x-ha.badge :variant="$variant">{{ $label }}</x-ha.badge><strong>{{ $counts->get($statut, 0) }}</strong></a>
        @endforeach
    </div>
    <x-ha.error-summary />
    <form method="GET" action="{{ route('my-reports') }}" class="ha-filter wx-filter" role="search" aria-label="Search my reports" data-auto-filter>
        <div class="ha-field ha-field--grow"><label class="ha-label" for="my-reports-search">Search</label><input id="my-reports-search" class="ha-input" name="q" type="search" maxlength="200" value="{{ request('q') }}" placeholder="Address or description…"></div>
        <div class="ha-field"><label class="ha-label" for="my-reports-status">Status</label><select id="my-reports-status" name="statut" class="ha-select"><option value="">All statuses</option>@foreach($labels as $statut => [$label])<option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ $label }}</option>@endforeach</select></div>
        <div class="ha-field"><label class="ha-label" for="my-reports-sort">Sort</label><select id="my-reports-sort" name="sort" class="ha-select"><option value="recent" @selected(request('sort', 'recent') === 'recent')>Newest first</option><option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option></select></div>
        <button class="ha-btn ha-btn--secondary">Apply</button><a class="ha-btn ha-btn--ghost" href="{{ route('my-reports') }}">Reset</a>
    </form>
    <div class="wx-list-head"><h2 class="wx-section-title">My reports <span class="ha-count-pill">{{ $signalements->total() }}</span></h2><span class="my-reports__page">Page {{ $signalements->currentPage() }} / {{ $signalements->lastPage() }}</span></div>
    <div class="my-reports__grid">
        @forelse($signalements as $signalement)
            @php([$statusLabel, $variant] = $labels[$signalement->statut])
            <article class="ha-card my-reports__card my-reports__card--{{ $variant }}">
                <header><span>Report #{{ $signalement->id }}</span><x-ha.badge :variant="$variant">{{ $statusLabel }}</x-ha.badge></header>
                <h3><x-ha.icon name="map-pin" size="sm" />{{ $signalement->adresse }}</h3>
                <p class="my-reports__description">{{ $signalement->description }}</p>
                <div class="my-reports__outage"><x-ha.icon name="zap" size="sm" /><div><span>Associated outage</span>@if($signalement->coupure)<strong>#{{ $signalement->coupure->id }} · {{ $signalement->coupure->quartier->nom }} — {{ $signalement->coupure->quartier->ville }}</strong><x-ha.badge :variant="$signalement->coupure->statut === 'résolue' ? 'success' : 'warning'">{{ $signalement->coupure->statut === 'résolue' ? 'Resolved outage' : 'Active outage' }}</x-ha.badge>@else<strong>No associated outage</strong>@endif</div></div>
                <footer><span><x-ha.icon name="clock" size="sm" />Submitted on {{ $signalement->created_at->format('d/m/Y \a\t H:i') }}</span>@if($signalement->updated_at->ne($signalement->created_at))<span>Updated on {{ $signalement->updated_at->format('d/m/Y \a\t H:i') }}</span>@endif</footer>
            </article>
        @empty
            <div class="ha-card my-reports__empty"><x-ha.empty-state icon="search" :title="$counts->sum() ? 'No results found' : 'No reports yet'" :description="$counts->sum() ? 'Change your filters to find your reports.' : 'Your reports will appear here once submitted.'" /><a class="ha-btn ha-btn--outline" href="{{ $counts->sum() ? route('my-reports') : route('outages.report.create') }}">{{ $counts->sum() ? 'Reset filters' : 'Report an outage' }}</a></div>
        @endforelse
    </div>
    <div class="ha-pagination"><nav aria-label="My reports pagination"><p>{{ $signalements->firstItem() ?? 0 }}–{{ $signalements->lastItem() ?? 0 }} of {{ $signalements->total() }} results</p><div class="my-reports__pagination">@if($signalements->onFirstPage())<button class="ha-btn ha-btn--outline" disabled>Previous</button>@else<a class="ha-btn ha-btn--outline" href="{{ $signalements->previousPageUrl() }}" rel="prev">Previous</a>@endif @if($signalements->hasMorePages())<a class="ha-btn ha-btn--primary" href="{{ $signalements->nextPageUrl() }}" rel="next">Next</a>@else<button class="ha-btn ha-btn--primary" disabled>Next</button>@endif</div></nav></div>
</div>
@endsection
