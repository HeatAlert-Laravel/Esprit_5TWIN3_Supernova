@extends('layouts.front')
@section('title', 'Reset Password')
@section('content')
<div class="mx-auto max-w-md rounded-3xl bg-white p-8 shadow-sm">
    <h1 class="text-3xl font-bold">Reset your password</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <div>
            <label for="email" class="block font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">
            @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password" class="block font-medium">New password</label>
            <input id="password" name="password" type="password" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">
            @error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="block font-medium">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">
            @error('password_confirmation')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>
        <button class="w-full rounded-lg bg-orange-600 p-3 font-semibold text-white hover:bg-orange-700">Reset password</button>
    </form>
</div>
@endsection
