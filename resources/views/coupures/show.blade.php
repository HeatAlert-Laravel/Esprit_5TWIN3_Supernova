@extends('layouts.admin')
@section('title', __('Outage'))
@section('content')
<x-ha.page-header :title="__('Outage')"><x-slot:actions><a class="ha-btn ha-btn--outline" href="{{ route('admin.coupures.index') }}">{{ __('Back') }}</a><a class="ha-btn ha-btn--primary" href="{{ route('admin.coupures.edit', $coupure) }}">{{ __('Edit') }}</a></x-slot:actions></x-ha.page-header>
<div class="ha-card"><dl><dt class="ha-label">{{ __('Neighborhood') }}</dt><dd>{{ $coupure->quartier->nom }}</dd>
<dt class="ha-label">{{ __('Type') }}</dt><dd>{{ __($coupure->type) }}</dd>
<dt class="ha-label">{{ __('Status') }}</dt><dd>{{ __($coupure->statut) }}</dd>
<dt class="ha-label">{{ __('Start date') }}</dt><dd>{{ $coupure->date_debut->format('d/m/Y H:i') }}</dd>
<dt class="ha-label">{{ __('Estimated end') }}</dt><dd>{{ $coupure->date_fin_estimee?->format('d/m/Y H:i') ?? __('Unknown') }}</dd>
<dt class="ha-label">{{ __('Description') }}</dt><dd>{{ $coupure->description }}</dd>
<dt class="ha-label">{{ __('Reports') }}</dt><dd>{{ $coupure->signalements->count() }}</dd></dl></div>
<section class="ha-card ha-outage-ai" data-outage-summary data-url="{{ route('admin.coupures.summary', $coupure) }}" aria-labelledby="outage-summary-title">
    <div class="ha-outage-ai__header">
        <div class="ha-outage-ai__heading">
            <span class="ha-outage-ai__icon"><x-ha.icon name="zap" /></span>
            <div><span class="ha-outage-ai__eyebrow">HEATALERT ASSISTANT</span><h2 id="outage-summary-title">AI report summary</h2></div>
        </div>
        @if($coupure->signalements->isNotEmpty())
            <button type="button" class="ha-btn ha-btn--primary ha-outage-ai__button" data-summary-button><x-ha.icon name="zap" size="sm" /><span data-summary-button-label>Summarize reports with AI</span></button>
        @endif
    </div>
    <p class="ha-outage-ai__intro">Key information from the reports, brought together in a clear summary.</p>
    @if($coupure->signalements->isEmpty())
        <p class="ha-outage-ai__empty">No reports are associated with this outage yet.</p>
    @else
        <noscript><p>Enable JavaScript to generate the summary on this page.</p></noscript>
    @endif
    <div class="ha-outage-ai__loading" data-summary-loading role="status" hidden><span class="ha-outage-ai__spinner" aria-hidden="true"></span><div><strong>Analyzing reports…</strong><span>Your summary will be ready shortly.</span></div></div>
    <p class="ha-outage-ai__error" data-summary-error role="alert" hidden></p>
    <div class="ha-outage-ai__result" data-summary-result aria-live="polite" hidden>
        <div class="ha-outage-ai__meta"><span class="ha-outage-ai__count" data-summary-count></span><span class="ha-outage-ai__location"><x-ha.icon name="map-pin" size="sm" /><span data-summary-location></span></span><span class="ha-outage-ai__provider" data-summary-provider></span></div>
        <div class="ha-outage-ai__synthesis"><span class="ha-outage-ai__eyebrow">OVERVIEW</span><p data-summary-text></p></div>
        <div class="ha-outage-ai__grid">
            <div class="ha-outage-ai__block"><h3><span class="ha-outage-ai__mini-icon"><x-ha.icon name="plug" size="sm" /></span>Reported issues</h3><ul data-summary-problems></ul></div>
            <div class="ha-outage-ai__block ha-outage-ai__block--attention"><h3><span class="ha-outage-ai__mini-icon"><x-ha.icon name="alert-triangle" size="sm" /></span>Points to review</h3><ul data-summary-attention></ul></div>
        </div>
        <div class="ha-outage-ai__footer"><p><x-ha.icon name="eye" size="sm" />AI-assisted summary · Reported information still needs to be verified.</p><details><summary>About this summary</summary><p data-summary-note></p><p>Cleaned descriptions are analyzed by Gemini, or OpenRouter as a backup.</p></details></div>
    </div>
</section>
<div class="ha-card"><h2 class="ha-card__title">{{ __('Reports') }}</h2>@forelse($coupure->signalements as $signalement)<p><a href="{{ route('admin.signalements.show', $signalement) }}">{{ $signalement->user->name }} — {{ $signalement->adresse }}</a> — {{ __($signalement->statut) }}</p>@empty<p>{{ __('No records') }}</p>@endforelse</div>
@endsection

