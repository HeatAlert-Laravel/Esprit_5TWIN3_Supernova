@extends('layouts.app')
@section('title', __('Active outages'))
@section('content')
<div class="outages-page">
    <section class="wx-hero outages-hero" aria-labelledby="outages-title">
        <div>
            <p class="ha-eyebrow">HeatAlert · Power network updates</p>
            <h1 id="outages-title">{{ __('Active outages') }}</h1>
            <p class="wx-hero__summary"><strong>{{ $coupures->total() }} {{ $coupures->total() > 1 ? 'outages match' : 'outage matches' }} your selection.</strong></p>
            <p class="wx-hero__hint">Check outages in your neighborhood and let us know if you are affected.</p>
        </div>
        <div class="outages-hero__action">
            <span class="outages-hero__symbol" aria-hidden="true"><x-ha.icon name="zap" /></span>
            <div><span class="outages-hero__action-title">Affected by a power outage?</span><p>Your report helps us keep track of the situation.</p>
                <div class="outages-hero__buttons">
                    <a class="ha-btn ha-btn--primary" href="{{ route('outages.report.create') }}"><x-ha.icon name="plus" size="sm" />{{ __('Report an outage') }}</a>
                    @if(auth()->user()?->role === 'USER')
                        <a class="ha-btn ha-btn--outline" href="{{ route('my-reports') }}"><x-ha.icon name="users" size="sm" />My reports</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <x-ha.error-summary />
    <nav class="ha-advice-situations" aria-label="Filter outages by type">
        @foreach(['' => 'All outages', 'en cours' => 'Ongoing', 'prévue' => 'Planned'] as $value => $label)
            <a href="{{ route('outages', array_merge(request()->except('page', 'type', 'statut'), $value ? ['type' => $value] : [])) }}" class="ha-btn {{ request('type', '') === $value ? 'ha-btn--primary' : 'ha-btn--outline' }}" @if(request('type', '') === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="outages-filter-wrap">@include('coupures._filters')</div>
    <div class="wx-list-head outages-list-head">
        <h2 class="wx-section-title">Outages <span class="ha-count-pill">{{ $coupures->total() }}</span></h2>
        <span class="outages-list-head__hint"><x-ha.icon name="sliders" size="sm" />{{ request('sort') === 'oldest' ? 'Oldest first' : 'Newest first' }}</span>
    </div>
    <div class="wx-grid outages-grid">
        @forelse($coupures as $coupure)
            @php($ongoing = $coupure->type === 'en cours')
            <article class="wx-card {{ $ongoing ? 'wx-card--orange' : 'wx-card--jaune' }} outage-card">
                <header class="wx-card__top">
                    <span class="wx-card__level"><x-ha.icon name="zap" size="sm" />{{ __($coupure->type) }}</span>
                    <x-ha.badge :variant="$ongoing ? 'orange' : 'warning'">{{ __($coupure->statut) }}</x-ha.badge>
                </header>
                <div class="wx-card__body">
                    <div><h3 class="wx-card__title">{{ $coupure->quartier->nom }}</h3><p class="wx-card__place"><x-ha.icon name="map-pin" size="sm" />{{ $coupure->quartier->ville }}</p></div>
                    <span class="outage-card__symbol" aria-hidden="true"><x-ha.icon :name="$ongoing ? 'plug' : 'clock'" /></span>
                </div>
                <dl class="outage-card__dates">
                    <div><dt><x-ha.icon name="clock" size="sm" />{{ __('Start date') }}</dt><dd><time datetime="{{ $coupure->date_debut->toIso8601String() }}">{{ $coupure->date_debut->format('d/m/Y') }}<span>{{ $coupure->date_debut->format('H:i') }}</span></time></dd></div>
                    <div><dt><x-ha.icon name="clock" size="sm" />{{ __('Estimated end') }}</dt><dd>@if($coupure->date_fin_estimee)<time datetime="{{ $coupure->date_fin_estimee->toIso8601String() }}">{{ $coupure->date_fin_estimee->format('d/m/Y') }}<span>{{ $coupure->date_fin_estimee->format('H:i') }}</span></time>@else<span class="outage-card__unknown">{{ __('Unknown') }}</span>@endif</dd></div>
                </dl>
                <p class="outage-card__description">{{ $coupure->description ?: 'No further details are available yet.' }}</p>
                <footer class="outage-card__footer"><span><x-ha.icon name="users" size="sm" />{{ __('Reports') }}: <strong>{{ $coupure->signalements_count }}</strong></span><span class="outage-card__reference">#{{ $coupure->id }}</span></footer>
            </article>
        @empty
            <div class="ha-card wx-grid__empty"><x-ha.empty-state icon="plug" :title="__('No active outages')" description="No outages match your search. Change your criteria or reset the filters." /><a class="ha-btn ha-btn--outline" href="{{ route('outages') }}">Reset filters</a></div>
        @endforelse
    </div>
    <div class="ha-pagination">{{ $coupures->links() }}</div>
</div>
@endsection
