@extends('layouts.front')
@section('title', __('Active outages'))
@section('content')
<x-ha.page-header :title="__('Active outages')"><x-slot:actions><a class="ha-btn ha-btn--primary" href="{{ route('outages.report.create') }}">{{ __('Report an outage') }}</a></x-slot:actions></x-ha.page-header>
<x-ha.error-summary />
<form novalidate method="GET" action="{{ route('outages') }}" class="ha-filter">
<div class="ha-field ha-field--grow"><label class="ha-label" for="quartier_id">{{ __('Neighborhood') }}</label><select id="quartier_id" name="quartier_id" class="ha-select"><option value="">{{ __('All neighborhoods') }}</option>@foreach($quartiers as $quartier)<option value="{{ $quartier->id }}" @selected((string) $quartierId === (string) $quartier->id)>{{ $quartier->nom }} — {{ $quartier->ville }}</option>@endforeach</select></div>
<button class="ha-btn ha-btn--secondary">{{ __('Filter') }}</button><a class="ha-btn ha-btn--ghost" href="{{ route('outages') }}">{{ __('Reset') }}</a></form>
<div class="ha-grid ha-grid--2">@forelse($coupures as $coupure)<article class="ha-card">
<div class="ha-card__head"><h2 class="ha-card__title">{{ $coupure->quartier->nom }} — {{ $coupure->quartier->ville }}</h2><x-ha.badge variant="warning">{{ $coupure->type }}</x-ha.badge></div>
<p>{{ __('Start date') }}: {{ $coupure->date_debut->format('d/m/Y H:i') }}</p>
<p>{{ __('Estimated end') }}: {{ $coupure->date_fin_estimee?->format('d/m/Y H:i') ?? __('Unknown') }}</p>
<p>{{ $coupure->description }}</p><p>{{ __('Reports') }}: {{ $coupure->signalements_count }}</p>
</article>@empty<div class="ha-card">{{ __('No active outages') }}</div>@endforelse</div>
<div class="ha-pagination">{{ $coupures->links() }}</div>
@endsection

