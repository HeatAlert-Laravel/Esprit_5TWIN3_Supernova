@extends('layouts.front')
@section('title', 'Home')
@section('content')
<div class="ha-hero">
    <div>
        <x-ha.badge variant="cool" :dot="false">Community heat preparedness</x-ha.badge>
        <h1 class="ha-hero__title">Stay ready when <span class="ha-accent">temperatures</span> rise.</h1>
        <p class="ha-hero__lead">HeatAlert brings neighborhood information and your household’s needs into one clear place. Prepare for heat and protect sensitive equipment.</p>
        <div class="ha-hero__cta">
            <a href="{{ auth()->check() ? route('my-profile') : route('register') }}" class="ha-btn ha-btn--primary">Prepare my household<x-ha.icon name="arrow-right" size="sm" /></a>
            <a href="{{ route('weather-alerts') }}" class="ha-btn ha-btn--outline">Weather alerts</a>
        </div>
        <ul class="ha-reassure" aria-label="Good to know">
            <li><x-ha.icon name="check" size="sm" />Free for residents</li>
            <li><x-ha.icon name="clock" size="sm" />Takes a few minutes</li>
            <li><x-ha.icon name="shield-check" size="sm" />Used for heat preparedness</li>
        </ul>
    </div>
    @include('partials.readiness-card')
</div>

<section class="ha-section" aria-labelledby="modules-title">
    <div class="ha-section__head">
        <h2 id="modules-title">{{ __('Know what is happening nearby') }}</h2>
        <p>{{ __('Check neighborhood alerts, plan around power interruptions, and find a place to cool down.') }}</p>
    </div>
    <div class="ha-grid ha-grid--3">
        <a href="{{ route('weather-alerts') }}" class="ha-card ha-card--interactive ha-live-module" aria-label="Open weather alerts">
            <div class="ha-live-module__head">
                <span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="thermometer" /></span>
                <x-ha.badge variant="success" :dot="false">Live</x-ha.badge>
            </div>
            <h3>Weather Alerts</h3>
            <p>Real heat alerts for your neighborhood — check the level, the forecast maximum and the advice to follow.</p>
            <span class="ha-live-module__link">View alerts<x-ha.icon name="arrow-right" size="sm" /></span>
        </a>
        <a href="{{ route('outages') }}" class="ha-card ha-card--interactive ha-live-module" aria-label="{{ __('Open outages') }}">
            <div class="ha-live-module__head">
                <span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="zap" /></span>
                <x-ha.badge variant="success" :dot="false">{{ __('Available') }}</x-ha.badge>
            </div>
            <h3>{{ __('Outages') }}</h3>
            <p>{{ __('Check reported and planned power interruptions in your neighborhood.') }}</p>
            <span class="ha-live-module__link">{{ __('View outages') }}<x-ha.icon name="arrow-right" size="sm" class="rtl:rotate-180" /></span>
        </a>
        <a href="{{ route('cooling-points') }}" class="ha-card ha-card--interactive ha-live-module" aria-label="{{ __('Open cooling points') }}">
            <div class="ha-live-module__head">
                <span class="ha-icon-chip"><x-ha.icon name="snowflake" /></span>
                <x-ha.badge variant="success" :dot="false">{{ __('Available') }}</x-ha.badge>
            </div>
            <h3>{{ __('Cooling Points') }}</h3>
            <p>{{ __('Explore the map and check opening hours, accessibility, and available facilities.') }}</p>
            <span class="ha-live-module__link">{{ __('Find cooling points') }}<x-ha.icon name="arrow-right" size="sm" class="rtl:rotate-180" /></span>
        </a>
    </div>
</section>

<section class="ha-section" aria-labelledby="guidance-title">
    <div class="ha-section__head">
        <h2 id="guidance-title">Simple ways to prepare</h2>
        <p>{{ __('Practical steps for hot days and power outages, from our published advice.') }}</p>
        <a href="{{ route('advice') }}" class="ha-btn ha-btn--outline">{{ __('Explore practical advice') }}<x-ha.icon name="arrow-right" size="sm" class="rtl:rotate-180" /></a>
    </div>
    @if($featuredAdvice->isNotEmpty())
        <div class="ha-grid ha-grid--3">
            @foreach($featuredAdvice as $conseil)
                <x-advice.card :conseil="$conseil" />
            @endforeach
        </div>
    @else
        <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('Advice is on its way')" :description="__('Practical advice will appear here as articles are published. You can already explore alerts, outages, and cooling points above.')" /></div>
    @endif
</section>
@endsection
