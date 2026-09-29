@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="mb-8"><p class="text-sm font-bold uppercase tracking-widest text-orange-600">HeatAlert overview</p><h1 class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">Community dashboard</h1><p class="mt-2 text-gray-600 dark:text-gray-300">Resident and equipment information from the local database.</p></div>
<div class="grid gap-5 md:grid-cols-3">
    @foreach ([['Registered users', $usersCount], ['Household profiles', $profilesCount], ['Sensitive equipment', $equipmentCount]] as [$label,$count])<div class="rounded-2xl border border-orange-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900"><p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $label }}</p><p class="mt-4 text-4xl font-bold text-orange-700 dark:text-orange-300">{{ $count }}</p></div>@endforeach
</div>
<div class="mt-6 grid gap-5 md:grid-cols-3">@foreach (['Active alerts', 'Ongoing outages', 'Cooling points'] as $heading)<div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"><h2 class="font-bold text-gray-900 dark:text-white">{{ $heading }}</h2><p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Module pending team integration. No live data yet.</p></div>@endforeach</div>
<div class="mt-8 flex flex-wrap gap-3"><a href="{{ route('admin.profiles.index') }}" class="rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">Manage profiles</a><a href="{{ route('admin.equipment.index') }}" class="rounded-lg border border-orange-300 px-5 py-3 font-semibold text-orange-800 dark:text-orange-200">Manage equipment</a></div>
@endsection
