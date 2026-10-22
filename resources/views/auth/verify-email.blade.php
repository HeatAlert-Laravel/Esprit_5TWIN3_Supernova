@extends('layouts.app')
@section('title', 'Verify email')
@section('content')
<section class="ha-card ha-auth-card">
    <x-logo variant="light" :size="28" :href="route('home')" class="ha-auth__logo" />
    <h1>Verify your email address</h1>
    <p>We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Open it to manage your household, equipment and saved advice.</p>
    <p class="ha-muted">Check your spam folder, or request a new link below.</p>
    <form method="POST" action="{{ route('verification.send') }}">@csrf
        <button class="ha-btn ha-btn--primary">Resend verification email</button>
    </form>
    <p class="mt-6"><a class="ha-link" href="{{ route('account.edit') }}">Account settings</a> — update your address if needed.</p>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="ha-btn ha-btn--outline">Log out</button></form>
</section>
@endsection
