@props(['level', 'short' => false])
{{-- Visual mapping of the EXISTING sensitive_equipments.priority_level values (low / medium / high). --}}
@php
    $variant = ['high' => 'danger', 'medium' => 'warning', 'low' => 'cool'][$level] ?? 'neutral';
    $label = ucfirst((string) $level);
@endphp
<x-ha.badge :variant="$variant" {{ $attributes }}>{{ $short ? $label : 'Priority: '.$label }}</x-ha.badge>
