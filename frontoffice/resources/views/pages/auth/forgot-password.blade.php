@extends('layouts.front')
@section('title', 'Forgot Password')
@section('content')
<div class="ha-card ha-auth-card">
    <a href="{{ route('login') }}" class="ha-back"><x-ha.icon name="arrow-left" size="sm" />Back to login</a>
    <h1>Forgot your password?</h1>
    <p>Enter your email address and we will send you a reset link.</p>
    <form method="POST" action="{{ route('password.email') }}" class="ha-form-grid">
        @csrf
        <x-ha.input name="email" label="Email" type="email" autocomplete="email" :value="old('email')" required autofocus />
        <button class="ha-btn ha-btn--primary ha-btn--block">Send reset link</button>
    </form>
</div>
@endsection
