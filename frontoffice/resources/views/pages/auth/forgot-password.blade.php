@extends('layouts.front')
@section('title', 'Forgot Password')
@section('content')
<div class="mx-auto max-w-md rounded-3xl bg-white p-8 shadow-sm">
    <h1 class="text-3xl font-bold">Forgot your password?</h1>
    <p class="mt-2 text-gray-600">Enter your email address and we will send you a reset link.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border border-gray-300 p-3">
            @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button class="w-full rounded-lg bg-orange-600 p-3 font-semibold text-white hover:bg-orange-700">Send reset link</button>
    </form>
    <a href="{{ route('login') }}" class="mt-5 inline-block text-sm font-semibold text-orange-700">Back to login</a>
</div>
@endsection
