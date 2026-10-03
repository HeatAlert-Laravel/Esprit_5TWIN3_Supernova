@props(['kind', 'on'])
{{-- Yes/no badge for TypeEquipement.sensitive_to_heat (kind="heat") and sensitive_to_outage (kind="outage"). --}}
@php
    $label = $kind === 'heat' ? 'heat-sensitive' : 'outage-sensitive';
@endphp
@if($on)
    <x-ha.badge :variant="$kind === 'heat' ? 'warning' : 'info'" {{ $attributes }}>{{ ucfirst($label) }}</x-ha.badge>
@else
    <x-ha.badge variant="neutral" :dot="false" {{ $attributes }}>Not {{ $label }}</x-ha.badge>
@endif
