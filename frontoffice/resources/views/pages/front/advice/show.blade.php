@extends('layouts.front')
@section('title', $conseil->titre)
@section('content')
@if($preview)
    <div class="ha-alert ha-alert--warning" role="status"><x-ha.icon name="eye" /><div><strong>{{ __('Administrator preview') }}</strong><p>{{ $conseil->actif ? __('This article is published. You are viewing the resident layout.') : __('This article is a draft. Residents cannot see it yet.') }}</p><a href="{{ rtrim(config('app.backoffice_url'), '/') }}/admin/conseils/{{ $conseil->id }}/edit" class="ha-btn ha-btn--ghost ha-btn--sm">{{ __('Back to editing') }}</a></div></div>
@endif
<x-ha.page-header :title="$conseil->titre" :eyebrow="$conseil->categorieConseil->nom" :breadcrumbs="[[__('Home'), route('home')], [__('Advice'), route('advice')], [$conseil->titre, null]]">
    <x-slot:badges><x-ha.badge variant="cool">{{ $conseil->situationLabel() }}</x-ha.badge><x-ha.badge variant="neutral">{{ $conseil->audienceLabel() }}</x-ha.badge></x-slot:badges>
    <x-slot:actions>@unless($preview)<x-advice.bookmark :conseil="$conseil" :return-to="$returnTo" :on-article="true" />@endunless</x-slot:actions>
</x-ha.page-header>
<article class="ha-card ha-advice-reading">
    <div class="ha-advice-meta ha-advice-reading-meta"><span><x-ha.icon name="clock" size="sm" />{{ __(':minutes min read', ['minutes' => $conseil->readingMinutes()]) }}</span><span>{{ __('Updated :date', ['date' => $conseil->updated_at->format('d M Y')]) }}</span></div>
    <p class="ha-advice-summary">{{ $conseil->resume }}</p>
    <div class="ha-advice-body ha-article-content">{!! $conseil->bodyHtml() !!}</div>
</article>
<div class="ha-advice-back"><a class="ha-btn ha-btn--outline" href="{{ $backUrl }}"><x-ha.icon name="arrow-left" size="sm" class="rtl:rotate-180" />{{ __('Back to results') }}</a><a class="ha-btn ha-btn--ghost" href="{{ route('advice', ['category' => $conseil->categorie_conseil_id]) }}">{{ __('More in :category', ['category' => $conseil->categorieConseil->nom]) }}</a></div>
@if($related->isNotEmpty())
    <section aria-labelledby="related-heading"><div class="ha-advice-category-head"><h2 id="related-heading">{{ __('You might also find these useful') }}</h2></div><div class="ha-grid ha-grid--3">@foreach($related as $article)<x-advice.card :conseil="$article" :return-to="$returnTo" />@endforeach</div></section>
@endif
@endsection
