@props(['kind' => 'cloudy', 'day' => true, 'size' => 48])
{{-- Weather condition icon. kind: clear, partly, cloudy, fog, rain, snow, thunder. Decorative: the condition is always written next to it. --}}
<svg {{ $attributes->class('wx-icon') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
    @php
        $sun = '<g stroke="#F5A623" stroke-width="3" stroke-linecap="round"><path d="M32 8v6M32 50v6M8 32h6M50 32h6M15 15l4 4M45 45l4 4M49 15l-4 4M19 45l-4 4"/></g><circle cx="32" cy="32" r="11" fill="#FFC83D"/>';
        $moon = '<path d="M40 14a18 18 0 1 0 10 31A16 16 0 0 1 40 14z" fill="#C9D3E6" stroke="#9DB0CF" stroke-width="1.5"/>';
        $cloud = fn (string $fill = '#EEF2F7', string $stroke = '#B5C2D3') => '<path d="M19 47h27a10 10 0 0 0 1.5-19.9A14 14 0 0 0 20.5 30 9 9 0 0 0 19 47z" fill="'.$fill.'" stroke="'.$stroke.'" stroke-width="2" stroke-linejoin="round"/>';
    @endphp
    @switch($kind)
        @case('clear')
            @if($day){!! $sun !!}@else{!! $moon !!}@endif
            @break
        @case('partly')
            <g transform="translate(-8 -10) scale(.75)">@if($day){!! $sun !!}@else{!! $moon !!}@endif</g>
            {!! $cloud() !!}
            @break
        @case('fog')
            {!! $cloud('#E3E8EF') !!}
            <g stroke="#9DB0CF" stroke-width="3" stroke-linecap="round"><path d="M16 53h32M22 59h22"/></g>
            @break
        @case('rain')
            {!! $cloud('#D5DCE6', '#9DB0CF') !!}
            <g stroke="#3B82F6" stroke-width="3" stroke-linecap="round"><path d="M24 52l-3 7M34 52l-3 7M44 52l-3 7"/></g>
            @break
        @case('snow')
            {!! $cloud('#E3E8EF') !!}
            <g fill="#8FB8F0"><circle cx="24" cy="54" r="2.5"/><circle cx="34" cy="58" r="2.5"/><circle cx="44" cy="54" r="2.5"/></g>
            @break
        @case('thunder')
            {!! $cloud('#B8C2D0', '#7F8FA6') !!}
            <path d="M35 44l-7 11h6l-3 8 11-13h-6l4-6z" fill="#FFC83D" stroke="#E8A317" stroke-width="1.5" stroke-linejoin="round"/>
            @break
        @default
            {!! $cloud() !!}
    @endswitch
</svg>
