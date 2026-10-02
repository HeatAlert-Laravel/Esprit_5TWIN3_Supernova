@props(['title', 'description', 'icon' => 'info', 'href' => null, 'tag' => 'Coming soon'])
{{-- Dashed slot for a module owned by another team member: reads as "planned", never as broken. --}}
@php($element = $href ? 'a' : 'div')
<{{ $element }} @if($href) href="{{ $href }}" @endif {{ $attributes->class('ha-slot') }}>
    <div class="ha-slot__head">
        <span class="ha-icon-chip"><x-ha.icon :name="$icon" /></span>
        <x-ha.badge variant="neutral" :dot="false">{{ $tag }}</x-ha.badge>
    </div>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>
</{{ $element }}>
