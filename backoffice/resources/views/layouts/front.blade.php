<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') | HeatAlert</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-orange-50 text-gray-900">
    @include('partials.front-navbar')
    <main class="mx-auto min-h-[70vh] max-w-6xl px-5 py-10 sm:py-14">
        @if(session('status'))<div role="status" class="mb-6 rounded-xl bg-green-100 p-4 text-green-900">{{ session('status') }}</div>@endif
        @yield('content')
    </main>
    @include('partials.front-footer')
</body>
</html>
