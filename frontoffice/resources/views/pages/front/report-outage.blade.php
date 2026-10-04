@extends('layouts.front')
@section('title', __('Report an outage'))
@section('content')
<x-ha.page-header :title="__('Report an outage')" />
<x-ha.error-summary />
<form novalidate method="POST" action="{{ route('outages.report.store') }}" class="ha-form"><div class="ha-card">
@csrf
@php($signalement = null)
<x-ha.field name="coupure_id" :label="__('Associated outage')"><select id="coupure_id" name="coupure_id" class="ha-select" ><option value="">{{ __('None') }}</option>@foreach($coupures as $coupureOption)<option value="{{ $coupureOption->id }}" @selected((string) old('coupure_id', $signalement?->coupure_id) === (string) $coupureOption->id)>#{{ $coupureOption->id }} — {{ $coupureOption->quartier->nom }} — {{ $coupureOption->date_debut->format('d/m/Y H:i') }} ({{ $coupureOption->statut }})</option>@endforeach</select></x-ha.field>
<x-ha.input name="adresse" :label="__('Address')" :value="old('adresse', $signalement?->adresse)" required maxlength="255" />
<x-ha.field name="description" :label="__('Description')"><textarea id="description" name="description" class="ha-textarea" required maxlength="10000" rows="5">{{ old('description', $signalement?->description) }}</textarea></x-ha.field>
<div class="ha-form-actions"><a class="ha-btn ha-btn--outline" href="{{ route('outages') }}">{{ __('Cancel') }}</a><button class="ha-btn ha-btn--primary">{{ __('Submit report') }}</button></div>
</div></form>
@endsection

