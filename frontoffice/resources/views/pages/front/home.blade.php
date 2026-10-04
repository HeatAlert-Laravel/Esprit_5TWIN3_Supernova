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
        <h2 id="modules-title">More on the way</h2>
        <p>Heat alerts are live. The rest of the team is still building Outages and Cooling Points — no outage or cooling data appears here yet.</p>
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
        <x-ha.module-slot title="Outages" description="Find updates during power interruptions." icon="zap" :href="route('outages')" />
        <x-ha.module-slot title="Cooling Points" description="Locate nearby spaces to cool down." icon="snowflake" :href="route('cooling-points')" />
    </div>
</section>

<section class="ha-section" aria-labelledby="guidance-title">
    <div class="ha-section__head">
        <h2 id="guidance-title">Simple ways to prepare</h2>
        <p>General habits that help during hot weather.</p>
    </div>
    <div class="ha-grid ha-grid--3">
        <div class="ha-card ha-tip"><span class="ha-icon-chip"><x-ha.icon name="sun" /></span><div><h3>Plan for hot days</h3><p>Keep water within reach and avoid the hottest hours when you can.</p></div></div>
        <div class="ha-card ha-tip"><span class="ha-icon-chip"><x-ha.icon name="users" /></span><div><h3>Look out for neighbors</h3><p>Check in on people who may find the heat harder to handle.</p></div></div>
        <div class="ha-card ha-tip"><span class="ha-icon-chip"><x-ha.icon name="plug" /></span><div><h3>List your key devices</h3><p>Note equipment that needs power or cooling so your household is easier to support.</p></div></div>
    </div>
</section>
@endsection
