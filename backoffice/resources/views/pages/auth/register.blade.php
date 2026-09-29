@extends('layouts.front')
@section('title', 'Register')
@section('content')
<div class="mx-auto max-w-md rounded-3xl bg-white p-8 shadow-sm"><h1 class="text-3xl font-bold">Join HeatAlert</h1><p class="mt-2 text-gray-600">Create a resident account. New accounts have the USER role.</p>
<form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">@csrf
    @foreach ([['name','Name','text'],['email','Email','email']] as [$field,$label,$type])<div><label for="{{ $field }}" class="block font-medium">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">@error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>@endforeach
    @foreach ([['password','Password'],['password_confirmation','Confirm password']] as [$field,$label])<div><label for="{{ $field }}" class="block font-medium">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" type="password" required class="mt-2 w-full rounded-lg border border-gray-300 p-3">@error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>@endforeach
    <button class="w-full rounded-lg bg-orange-600 p-3 font-semibold text-white hover:bg-orange-700">Create account</button>
</form><p class="mt-5 text-sm">Already registered? <a class="font-semibold text-orange-700" href="{{ route('login') }}">Log in</a></p></div>
@endsection
