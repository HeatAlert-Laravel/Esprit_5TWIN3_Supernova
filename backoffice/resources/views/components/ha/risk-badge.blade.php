@props(['level', 'short' => false])
{{-- Badge for TypeEquipement.risk_level (low / medium / high / critical). The single place that maps a risk level to a colour. --}}
@php
    $variant = ['critical' => 'critical', 'high' => 'danger', 'medium' => 'warning', 'low' => 'cool'][$level] ?? 'neutral';
    $label = strtoupper((string) $level);
@endphp
<x-ha.badge :variant="$variant" {{ $attributes }}>{{ $short ? $label : 'Risk: '.$label }}</x-ha.badge>
