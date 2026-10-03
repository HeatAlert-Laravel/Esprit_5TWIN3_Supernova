@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php
    $equipmentCount = $equipmentStats['total'];
    $riskRows = [];
    foreach (array_reverse(\App\Models\TypeEquipement::RISK_LEVELS) as $level) {
        $riskRows[$level] = [ucfirst($level).' risk', $equipmentByRisk[$level] ?? 0];
    }
@endphp
<x-ha.page-header title="Community dashboard" eyebrow="HeatAlert overview" description="Resident and equipment information from the local database.">
    <x-slot:actions>
        <a href="{{ route('admin.profiles.create') }}" class="ha-btn ha-btn--outline"><x-ha.icon name="plus" size="sm" />Add profile</a>
        <a href="{{ route('admin.profiles.index') }}" class="ha-btn ha-btn--primary">Manage profiles<x-ha.icon name="arrow-right" size="sm" /></a>
    </x-slot:actions>
</x-ha.page-header>

<section aria-label="Key figures" class="ha-grid ha-grid--3">
    <x-ha.kpi-card label="Registered users" :value="$usersCount" icon="users">
        <x-ha.badge :variant="$residentsWithoutProfile > 0 ? 'warning' : 'success'">{{ $residentsWithoutProfile }} without a profile</x-ha.badge>
    </x-ha.kpi-card>
    <x-ha.kpi-card label="Household profiles" :value="$profilesCount" icon="home">
        <x-ha.badge variant="success">{{ $completeProfilesCount }} complete</x-ha.badge>
        <x-ha.badge :variant="$incompleteProfilesCount > 0 ? 'warning' : 'neutral'">{{ $incompleteProfilesCount }} incomplete</x-ha.badge>
    </x-ha.kpi-card>
    <x-ha.kpi-card label="Sensitive equipment" :value="$equipmentCount" icon="plug">
        <x-ha.badge variant="critical">{{ $riskRows['critical'][1] }} critical</x-ha.badge>
        <x-ha.badge variant="danger">{{ $riskRows['high'][1] }} high</x-ha.badge>
        <x-ha.badge variant="warning">{{ $riskRows['medium'][1] }} medium</x-ha.badge>
        <x-ha.badge variant="cool">{{ $riskRows['low'][1] }} low</x-ha.badge>
    </x-ha.kpi-card>
</section>

<section aria-label="Equipment key figures" class="ha-grid ha-grid--3 mt-5">
    <x-ha.kpi-card label="High or critical risk" :value="$equipmentStats['high_risk']" icon="alert-triangle">
        <span>of {{ $equipmentCount }} equipment</span>
    </x-ha.kpi-card>
    <x-ha.kpi-card label="Heat-sensitive equipment" :value="$equipmentStats['heat']" icon="thermometer">
        <span>according to equipment type</span>
    </x-ha.kpi-card>
    <x-ha.kpi-card label="Outage-sensitive equipment" :value="$equipmentStats['outage']" icon="zap">
        <span>according to equipment type</span>
    </x-ha.kpi-card>
</section>

<div class="ha-grid ha-grid--main mt-5">
    <section class="ha-card" aria-labelledby="check-first-title">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="check-first-title"><span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="alert-triangle" /></span>Households to check first</h2>
            <a href="{{ route('admin.profiles.index') }}" class="ha-btn ha-btn--ghost ha-btn--sm">All profiles</a>
        </div>
        @if($checkFirst->isEmpty())
            <x-ha.empty-state icon="circle-check" title="Nothing to check right now" description="Every profile has complete details and no equipment is rated high or critical risk." />
        @else
            <ul class="ha-list">
                @foreach($checkFirst as $household)
                    <li>
                        <div class="ha-cell-person">
                            <x-ha.avatar :name="$household->user->name" />
                            <div class="min-w-0">
                                <a class="ha-cell-person__name" href="{{ route('admin.profiles.show', $household) }}">{{ $household->user->name }}</a>
                                <span class="ha-cell-person__sub">{{ $household->neighborhood ?: 'Neighborhood not provided' }}</span>
                            </div>
                        </div>
                        <div class="ha-reasons">
                            @if($household->is_incomplete)<x-ha.badge variant="warning">Incomplete details</x-ha.badge>@endif
                            @if($household->high_risk_count > 0)<x-ha.badge variant="danger">{{ $household->high_risk_count }} high-risk {{ \Illuminate\Support\Str::plural('item', $household->high_risk_count) }}</x-ha.badge>@endif
                            @if($household->has_fragile_person)<x-ha.badge variant="info">Fragile person noted</x-ha.badge>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="ha-rule-note"><strong>How this list works:</strong> a household appears when its profile has a blank required detail (phone, address or neighborhood) or at least one equipment item whose type is rated high or critical risk. Incomplete profiles come first, then households with more high-risk items. It is a data check, not an emergency or medical assessment.</p>
    </section>

    <section class="ha-card" aria-labelledby="risk-title">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="risk-title"><span class="ha-icon-chip"><x-ha.icon name="plug" /></span>Equipment by risk</h2>
        </div>
        @if($equipmentCount === 0)
            <x-ha.empty-state icon="plug" title="No equipment recorded yet" description="Residents add equipment from their profile page." />
        @else
            <ul class="ha-bars">
                @foreach($riskRows as $level => [$label, $total])
                    <li class="ha-bars__row">
                        <div class="ha-bars__head"><span>{{ $label }}</span><span class="ha-num">{{ $total }}</span></div>
                        <div class="ha-bars__track" aria-hidden="true"><div class="ha-bars__fill ha-bars__fill--{{ $level }}" style="width: {{ round($total / $equipmentCount * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
            <div class="ha-form-actions">
                <a href="{{ route('admin.type-equipements.index') }}" class="ha-btn ha-btn--ghost ha-btn--sm">Equipment types</a>
                <a href="{{ route('admin.equipment.index') }}" class="ha-btn ha-btn--outline ha-btn--sm">View equipment</a>
            </div>
        @endif
    </section>
</div>

<x-ha.divider>Planned modules</x-ha.divider>
<section aria-label="Planned modules" class="ha-grid ha-grid--3">
    <x-ha.module-slot title="Active alerts" description="Heat alerts will appear here once the Weather Alerts module is integrated." icon="alert-triangle" />
    <x-ha.module-slot title="Ongoing outages" description="Power outage tracking will be added by the Outages module." icon="zap" />
    <x-ha.module-slot title="Cooling points" description="Cooling point management will be added by the Cooling Points module." icon="snowflake" />
</section>
@endsection
