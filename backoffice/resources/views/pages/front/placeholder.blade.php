@extends('layouts.front')
@section('title', $title)
@section('content')
<div class="max-w-2xl rounded-3xl border border-orange-100 bg-white p-8 shadow-sm sm:p-12"><span class="text-sm font-bold uppercase tracking-widest text-orange-600">HeatAlert</span><h1 class="mt-3 text-4xl font-bold">{{ $title }}</h1><p class="mt-5 text-lg leading-8 text-gray-600">This section is reserved for the team member building the {{ strtolower($title) }} module. No live information is available here yet.</p><a class="mt-8 inline-block font-semibold text-orange-700" href="{{ route('home') }}">← Back home</a></div>
@endsection
