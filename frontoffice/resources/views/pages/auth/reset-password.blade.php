@extends('layouts.front')
@section('title', 'Reset Password')
@section('content')
<div class="ha-card ha-auth-card">
    <x-logo variant="light" :size="28" :href="route('home')" class="ha-auth__logo" />
    <h1>Reset your password</h1>
    <p>Choose a new password for your HeatAlert account.</p>
    <form novalidate method="POST" action="{{ route('password.update') }}" class="ha-form-grid">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')<div role="alert" class="ha-alert ha-alert--error"><x-ha.icon name="alert-triangle" /><div>{{ $message }}</div></div>@enderror
        <x-ha.input name="email" label="Email" type="email" autocomplete="email" :value="old('email', $email)" required />
        <x-ha.input name="password" label="New password" type="password" autocomplete="new-password" required toggle />
        <x-ha.input name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required toggle />
        <button class="ha-btn ha-btn--primary ha-btn--block">Save new password</button>
    </form>
</div>
@endsection
