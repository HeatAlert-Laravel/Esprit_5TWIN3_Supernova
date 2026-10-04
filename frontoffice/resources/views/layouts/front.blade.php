<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') | HeatAlert</title>
    @include('partials.brand-head')
    <script>document.documentElement.classList.add('js');</script>
    {{-- The Shade & Signal theme layer is loaded AFTER the base CSS so it can override it. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/heatalert-theme.css'])
</head>
<body>
    <a href="#main-content" class="ha-skip-link">Skip to content</a>
    @include('partials.front-status-strip')
    @include('partials.front-navbar')
    <main id="main-content" class="ha-container ha-main">
        <x-ha.flash />
        @yield('content')
    </main>
    @include('partials.front-footer')

    <div class="ha-confirm-modal" data-confirm-dialog hidden>
        <div class="ha-confirm-modal__backdrop" data-confirm-cancel></div>
        <section class="ha-confirm-modal__panel" role="dialog" aria-modal="true" aria-labelledby="ha-confirm-title">
            <span class="ha-icon-chip ha-icon-chip--ember"><x-ha.icon name="trash" /></span>
            <h2 id="ha-confirm-title">Confirm deletion</h2>
            <p data-confirm-message></p>
            <div class="ha-confirm-modal__actions">
                <button type="button" class="ha-btn ha-btn--outline" data-confirm-cancel>Cancel</button>
                <button type="button" class="ha-btn ha-btn--danger-soft" data-confirm-submit><x-ha.icon name="trash" size="sm" />Delete</button>
            </div>
        </section>
    </div>
</body>
</html>
