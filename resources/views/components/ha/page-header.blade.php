@props(['title', 'description' => null, 'eyebrow' => null, 'breadcrumbs' => []])
{{-- breadcrumbs: [[label, url|null], ...] — the last entry is the current page. Slots: badges, actions. --}}
<header class="ha-page-header">
    <div>
        @if($breadcrumbs)
            <nav aria-label="Breadcrumb">
                <ol class="ha-breadcrumb">
                    @foreach($breadcrumbs as [$crumbLabel, $crumbUrl])
                        <li>@if($crumbUrl && ! $loop->last)<a href="{{ $crumbUrl }}">{{ $crumbLabel }}</a>@else<span aria-current="page">{{ $crumbLabel }}</span>@endif</li>
                    @endforeach
                </ol>
            </nav>
        @endif
        @if($eyebrow)<p class="ha-eyebrow">{{ $eyebrow }}</p>@endif
        <div class="ha-page-header__title">
            <h1>{{ $title }}</h1>
            {{ $badges ?? '' }}
        </div>
        @if($description)<p class="ha-page-header__desc">{{ $description }}</p>@endif
    </div>
    @isset($actions)<div class="ha-page-header__actions">{{ $actions }}</div>@endisset
</header>
