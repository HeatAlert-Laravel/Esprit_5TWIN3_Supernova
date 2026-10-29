@extends('layouts.app')
@section('title', __('Active outages'))
@section('content')
<x-ha.page-header :title="__('Active outages')"><x-slot:actions><a class="ha-btn ha-btn--primary" href="{{ route('outages.report.create') }}">{{ __('Report an outage') }}</a></x-slot:actions></x-ha.page-header>
<x-ha.error-summary />
@include('coupures._filters')
<p class="mb-4">{{ $coupures->total() }} résultat(s)</p>
<div class="ha-grid ha-grid--2">@forelse($coupures as $coupure)<article class="ha-card">
<div class="ha-card__head"><h2 class="ha-card__title">{{ $coupure->quartier->nom }} — {{ $coupure->quartier->ville }}</h2><x-ha.badge variant="warning">{{ $coupure->type }}</x-ha.badge></div>
<p>{{ __('Start date') }}: {{ $coupure->date_debut->format('d/m/Y H:i') }}</p>
<p>{{ __('Estimated end') }}: {{ $coupure->date_fin_estimee?->format('d/m/Y H:i') ?? __('Unknown') }}</p>
<p>{{ $coupure->description }}</p><p>{{ __('Reports') }}: {{ $coupure->signalements_count }}</p>
</article>@empty<div class="ha-card">{{ __('No active outages') }}</div>@endforelse</div>
<div class="ha-pagination">{{ $coupures->links() }}</div>
@endsection

