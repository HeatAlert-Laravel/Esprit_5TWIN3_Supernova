@props(['variant' => 'neutral', 'dot' => true])
{{-- Status badge: pill, dot + text. Variants: success, warning, danger, info, cool, neutral. --}}
<span {{ $attributes->class(['ha-badge', 'ha-badge--'.$variant, 'ha-badge--plain' => ! $dot]) }}>{{ $slot }}</span>
