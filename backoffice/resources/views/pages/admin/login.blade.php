<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin login | HeatAlert</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/heatalert-theme.css'])
</head>
<body>
    <main class="ha-login">
        <div class="ha-login__card">
            <div class="ha-login__brand">
                <img src="{{ Vite::asset('resources/images/heat-alert-mark.svg') }}" alt="">
                <div><p>HeatAlert</p><h1>Admin login</h1></div>
            </div>
            <div class="ha-card">
                <x-ha.error-summary />
                <form method="POST" action="{{ route('login') }}" class="ha-form-grid">
                    @csrf
                    <x-ha.input name="email" label="Email" type="email" autocomplete="username" :value="old('email')" required autofocus />
                    <x-ha.input name="password" label="Password" type="password" autocomplete="current-password" required />
                    <button class="ha-btn ha-btn--primary ha-btn--block">Log in</button>
                </form>
            </div>
            <a href="{{ config('app.frontoffice_url') }}" class="ha-back mt-5"><x-ha.icon name="arrow-left" size="sm" />Public site</a>
        </div>
    </main>
</body>
</html>
