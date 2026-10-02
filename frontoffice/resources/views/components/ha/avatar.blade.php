@props(['name', 'size' => null])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('');
@endphp
<span {{ $attributes->class(['ha-avatar', 'ha-avatar--'.$size => $size]) }} aria-hidden="true">{{ $initials ?: '?' }}</span>
