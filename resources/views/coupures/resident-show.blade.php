@extends('layouts.app')
@section('title', 'Outage details')
@section('content')
<x-ha.page-header title="Outage details" :description="'Outage #'.$coupure->id">
    <x-slot:actions><a class="ha-btn ha-btn--outline" href="{{ route('outages') }}">Back to outages</a><a class="ha-btn ha-btn--outline" href="{{ route('my-reports') }}">My reports</a></x-slot:actions>
</x-ha.page-header>
<article class="ha-card outage-resident-detail">
    <div class="ha-card__head"><h2 class="ha-card__title">{{ $coupure->quartier->nom }} — {{ $coupure->quartier->ville }}</h2><x-ha.badge :variant="$coupure->statut === 'résolue' ? 'success' : 'warning'">{{ $coupure->statut === 'résolue' ? 'Resolved' : 'Active' }}</x-ha.badge></div>
    <p class="outage-resident-detail__type"><x-ha.icon name="zap" size="sm" />{{ $coupure->type === 'prévue' ? 'Planned outage' : 'Ongoing outage' }}</p>
    <dl class="outage-card__dates"><div><dt>Start date</dt><dd>{{ $coupure->date_debut->format('d M Y, H:i') }}</dd></div><div><dt>Estimated end</dt><dd>{{ $coupure->date_fin_estimee?->format('d M Y, H:i') ?? 'Not specified' }}</dd></div></dl>
    <p class="outage-resident-detail__description">{{ $coupure->description ?: 'No further details are available.' }}</p>
    @if($coupure->statut === 'active')<a class="ha-btn ha-btn--primary" href="{{ route('outages.report.create') }}">Report an outage</a>@else<p class="outage-resident-detail__resolved"><x-ha.icon name="circle-check" size="sm" />This outage has been resolved.</p>@endif
</article>
@endsection
