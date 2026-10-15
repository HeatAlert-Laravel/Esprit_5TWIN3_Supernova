{{--
    HeatAlert "Shelter" logo: inline SVG mark + optional wordmark.

    variant   light | dark | mono   (dark = for #0F3D3E backgrounds, mono = currentColor)
    size      icon height in px (minimum 16); the gap and wordmark size derive from it
    wordmark  show the "HeatAlert" wordmark next to the mark
    href      optional link target (wraps the lockup in an <a>)
    label     accessible name of the link (default "HeatAlert home")

    Presentation lives in .ha-logo (heatalert-theme.css). Extra attributes (class, x-show, ...) go on the outer element.
    This component is intentionally duplicated in frontoffice/ and backoffice/ (separate apps); keep both copies identical.
--}}
@props(['variant' => 'light', 'size' => 36, 'wordmark' => true, 'href' => null, 'label' => 'HeatAlert home'])
@php
    $variant = in_array($variant, ['light', 'dark', 'mono'], true) ? $variant : 'light';
    $size = max(16, (int) $size);
    [$sun, $arch, $text] = match ($variant) {
        'dark' => ['#E8590C', '#DCEFEC', '#FFFFFF'],
        'mono' => ['currentColor', 'currentColor', 'currentColor'],
        default => ['#E8590C', '#0F3D3E', '#0F3D3E'],
    };
    // Unique per rendered instance so several logos on one page never share (and break) the same <mask>.
    $maskId = 'ha-cut-' . str_replace('.', '', uniqid('', true));
    $tag = $href ? 'a' : 'span';
@endphp
<{{ $tag }} {{ $attributes->class('ha-logo')->merge(array_filter([
    'href' => $href,
    'aria-label' => $href ? $label : (! $wordmark ? 'HeatAlert' : null),
    'role' => (! $href && ! $wordmark) ? 'img' : null,
    'style' => "--ha-logo-size: {$size}px",
])) }}>
    <svg class="ha-logo__mark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false">
        <defs>
            <mask id="{{ $maskId }}" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
                <rect width="48" height="48" fill="#fff"/>
                <path d="M6 42V28a14 14 0 0 1 28 0v14z" fill="#000" stroke="#000" stroke-width="6" stroke-linejoin="round"/>
            </mask>
        </defs>
        <circle cx="33" cy="15" r="9" fill="{{ $sun }}" mask="url(#{{ $maskId }})"/>
        <path d="M6 42V28a14 14 0 0 1 28 0v14z" fill="{{ $arch }}"/>
    </svg>
    @if ($wordmark)
        <span class="ha-logo__word" style="color: {{ $text }}">HeatAlert</span>
    @endif
</{{ $tag }}>
