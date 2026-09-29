@extends('layouts.front')
@section('title', 'Login')
@section('content')
<div class="mx-auto max-w-md rounded-3xl bg-white p-8 shadow-sm"><h1 class="text-3xl font-bold">Welcome back</h1><p class="mt-2 text-gray-600">Sign in to manage your HeatAlert profile.</p>
<form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">@csrf
    <div><label for="email" class="block font-medium">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border border-gray-300 p-3">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="password" class="block font-medium">Password</label><input id="password" name="password" type="password" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">@error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>Remember me</label>
    <button class="w-full rounded-lg bg-orange-600 p-3 font-semibold text-white hover:bg-orange-700">Log in</button>
</form><p class="mt-5 text-sm">New here? <a class="font-semibold text-orange-700" href="{{ route('register') }}">Create an account</a></p></div>
@endsection
