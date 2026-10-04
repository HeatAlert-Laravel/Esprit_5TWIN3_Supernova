@extends('layouts.app')
@section('title', __('Report'))
@section('content')
<x-ha.page-header :title="__('Report')"><x-slot:actions><a class="ha-btn ha-btn--outline" href="{{ route('admin.signalements.index') }}">{{ __('Back') }}</a><a class="ha-btn ha-btn--primary" href="{{ route('admin.signalements.edit', $signalement) }}">{{ __('Edit') }}</a></x-slot:actions></x-ha.page-header>
<div class="ha-card"><dl><dt class="ha-label">{{ __('Resident') }}</dt><dd>{{ $signalement->user->name }}</dd>
<dt class="ha-label">{{ __('Associated outage') }}</dt><dd>{{ $signalement->coupure ? '#'.$signalement->coupure->id.' — '.$signalement->coupure->quartier->nom : __('None') }}</dd>
<dt class="ha-label">{{ __('Address') }}</dt><dd>{{ $signalement->adresse }}</dd>
<dt class="ha-label">{{ __('Description') }}</dt><dd>{{ $signalement->description }}</dd>
<dt class="ha-label">{{ __('Status') }}</dt><dd>{{ __($signalement->statut) }}</dd></dl></div>

@endsection

