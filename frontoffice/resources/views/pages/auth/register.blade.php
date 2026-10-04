@extends('layouts.front')
@section('title', 'Register')
@section('content')
<div class="ha-auth">
    <div class="ha-auth__form">
        <x-logo variant="light" :size="28" :href="route('home')" class="ha-auth__logo" />
        <h1>Join HeatAlert</h1>
        <p>Create a resident account to prepare your household.</p>
        <x-ha.error-summary />
        <form novalidate method="POST" action="{{ route('register') }}" class="ha-form-grid">
            @csrf
            <x-ha.input name="name" label="Name" autocomplete="name" :value="old('name')" required />
            <x-ha.input name="email" label="Email" type="email" autocomplete="email" :value="old('email')" required />
            <x-ha.input name="password" label="Password" type="password" autocomplete="new-password" required toggle />
            <x-ha.input name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required toggle />
            <button class="ha-btn ha-btn--primary ha-btn--block">Create account</button>
        </form>
        <p class="mt-6 ha-muted">Already registered? <a class="ha-link" href="{{ route('login') }}">Log in</a></p>
    </div>
    @include('partials.auth-panel')
</div>
@endsection
