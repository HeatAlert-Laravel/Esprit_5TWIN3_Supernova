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
</body>
</html>
