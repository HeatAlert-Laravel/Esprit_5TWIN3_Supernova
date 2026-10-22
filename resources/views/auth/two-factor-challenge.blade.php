@extends('layouts.app')
@section('title', 'Two-factor authentication')
@section('content')
<section class="ha-card ha-auth-card">
    <h1>Two-factor authentication</h1>
    <p>Enter the six-digit code from your authenticator app to finish signing in.</p>
    <x-ha.error-summary />
    <form method="POST" action="{{ route('two-factor.login.store') }}" class="ha-form-grid">@csrf
        <x-ha.input name="code" label="Authentication code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required autofocus />
        <button class="ha-btn ha-btn--primary">Verify code</button>
    </form>
    <details class="mt-6">
        <summary>Use a recovery code instead</summary>
        <p>Each recovery code works once.</p>
        <form method="POST" action="{{ route('two-factor.login.store') }}" class="ha-form-grid">@csrf
            <x-ha.input name="recovery_code" label="Recovery code" autocomplete="off" required />
            <button class="ha-btn ha-btn--outline">Use recovery code</button>
        </form>
    </details>
    <p class="mt-6"><a href="{{ route('login') }}" class="ha-link">Back to login</a></p>
</section>
@endsection
