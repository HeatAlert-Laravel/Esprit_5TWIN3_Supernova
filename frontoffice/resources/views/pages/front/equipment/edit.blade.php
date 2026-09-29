@extends('layouts.front')
@section('title', 'Edit equipment')
@section('content')
<div class="mx-auto max-w-2xl">
    <p class="text-sm font-bold uppercase tracking-widest text-orange-600">My Profile</p>
    <h1 class="mt-2 text-3xl font-bold">Edit sensitive equipment</h1>
    <p class="mt-3 text-gray-600">Update the details for {{ $equipment->name }}.</p>
    <form method="POST" action="{{ route('profile.equipment.update', $equipment) }}" class="mt-8 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
        @include('pages.front.equipment._form')
    </form>
</div>
@endsection
