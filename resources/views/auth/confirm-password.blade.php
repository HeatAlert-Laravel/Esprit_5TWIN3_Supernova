@extends('layouts.app')
@section('title', 'Confirm password')
@section('content')
<section class="ha-card ha-auth-card">
    <h1>Confirm your password</h1>
    <p>Enter your current password to manage sensitive account settings.</p>
    <x-ha.error-summary />
    <form method="POST" action="{{ route('password.confirm.store') }}" class="ha-form-grid">@csrf
        <x-ha.input name="password" label="Current password" type="password" autocomplete="current-password" required autofocus toggle />
        <button class="ha-btn ha-btn--primary">Confirm password</button>
        <a href="{{ route('account.edit') }}" class="ha-link">Back to account</a>
    </form>
</section>
@endsection
