@extends('layouts.app')
@section('title', __('Outage'))
@section('content')
<x-ha.page-header :title="__('Outage')"><x-slot:actions><a class="ha-btn ha-btn--outline" href="{{ route('admin.coupures.index') }}">{{ __('Back') }}</a><a class="ha-btn ha-btn--primary" href="{{ route('admin.coupures.edit', $coupure) }}">{{ __('Edit') }}</a></x-slot:actions></x-ha.page-header>
<div class="ha-card"><dl><dt class="ha-label">{{ __('Neighborhood') }}</dt><dd>{{ $coupure->quartier->nom }}</dd>
<dt class="ha-label">{{ __('Type') }}</dt><dd>{{ $coupure->type }}</dd>
<dt class="ha-label">{{ __('Status') }}</dt><dd>{{ $coupure->statut }}</dd>
<dt class="ha-label">{{ __('Start date') }}</dt><dd>{{ $coupure->date_debut->format('d/m/Y H:i') }}</dd>
<dt class="ha-label">{{ __('Estimated end') }}</dt><dd>{{ $coupure->date_fin_estimee?->format('d/m/Y H:i') ?? __('Unknown') }}</dd>
<dt class="ha-label">{{ __('Description') }}</dt><dd>{{ $coupure->description }}</dd>
<dt class="ha-label">{{ __('Reports') }}</dt><dd>{{ $coupure->signalements->count() }}</dd></dl></div>
<div class="ha-card"><h2 class="ha-card__title">{{ __('Reports') }}</h2>@forelse($coupure->signalements as $signalement)<p><a href="{{ route('admin.signalements.show', $signalement) }}">{{ $signalement->user->name }} — {{ $signalement->adresse }}</a> — {{ $signalement->statut }}</p>@empty<p>{{ __('No records') }}</p>@endforelse</div>
@endsection

