@props(['name', 'size' => null, 'src' => null])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp
@if($src)
<img src="{{ $src }}" alt="{{ $name }} profile picture" {{ $attributes->class(['ha-avatar', 'ha-avatar--'.$size => $size]) }}>
@else
<span {{ $attributes->class(['ha-avatar', 'ha-avatar--'.$size => $size]) }} aria-hidden="true">{{ $initials ?: '?' }}</span>
@endif
