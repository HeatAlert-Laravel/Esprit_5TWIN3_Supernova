@extends('layouts.front')
@section('title', 'Login')
@section('content')
<div class="ha-auth">
    <div class="ha-auth__form">
        <x-logo variant="light" :size="28" :href="route('home')" class="ha-auth__logo" />
        <h1>Welcome back</h1>
        <p>Sign in to manage your HeatAlert profile.</p>
        <x-ha.error-summary />
        <form method="POST" action="{{ route('login') }}" class="ha-form-grid">
            @csrf
            <x-ha.input name="email" label="Email" type="email" autocomplete="email" :value="old('email')" required autofocus />
            <x-ha.input name="password" label="Password" type="password" autocomplete="current-password" required toggle />
            <div class="ha-row-between">
                <label class="ha-check"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}><span class="ha-check__text">Remember me</span></label>
                <a class="ha-link" href="{{ route('password.request') }}">Forgot your password?</a>
            </div>
            <button class="ha-btn ha-btn--primary ha-btn--block">Log in</button>
        </form>
        <p class="mt-6 ha-muted">New here? <a class="ha-link" href="{{ route('register') }}">Create an account</a></p>
    </div>
    @include('partials.auth-panel')
</div>
@endsection
