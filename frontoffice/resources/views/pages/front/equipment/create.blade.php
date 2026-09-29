@extends('layouts.front')
@section('title', 'Add equipment')
@section('content')
<div class="mx-auto max-w-2xl">
    <p class="text-sm font-bold uppercase tracking-widest text-orange-600">My Profile</p>
    <h1 class="mt-2 text-3xl font-bold">Add sensitive equipment</h1>
    <p class="mt-3 text-gray-600">This equipment will be linked to your household profile.</p>
    <form method="POST" action="{{ route('profile.equipment.store') }}" class="mt-8 rounded-2xl bg-white p-6 shadow-sm sm:p-8">
        @include('pages.front.equipment._form', ['equipment' => null])
    </form>
</div>
@endsection
