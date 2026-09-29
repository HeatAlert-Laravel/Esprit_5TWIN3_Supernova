@extends('layouts.front')
@section('title', 'Home')
@section('content')
<div class="grid items-center gap-10 lg:grid-cols-2">
    <div>
        <span class="inline-flex rounded-full bg-orange-100 px-4 py-2 text-sm font-bold text-orange-800">Community heat preparedness</span>
        <h1 class="mt-5 text-4xl font-bold leading-tight text-gray-900 sm:text-6xl">Stay ready when temperatures rise.</h1>
        <p class="mt-6 max-w-xl text-lg leading-8 text-gray-600">HeatAlert brings neighborhood information and your household’s needs into one clear place. Prepare for heat and protect sensitive equipment.</p>
        <div class="mt-8 flex flex-wrap gap-3"><a href="{{ auth()->check() ? route('my-profile') : route('register') }}" class="rounded-xl bg-orange-600 px-6 py-3 font-semibold text-white hover:bg-orange-700">{{ auth()->check() ? 'View my profile' : 'Get started' }}</a><a href="{{ route('weather-alerts') }}" class="rounded-xl border border-orange-300 px-6 py-3 font-semibold text-orange-800 hover:bg-orange-100">Explore alerts</a></div>
    </div>
    <div class="rounded-3xl bg-gradient-to-br from-orange-500 via-orange-600 to-red-700 p-8 text-white shadow-xl sm:p-10">
        <div class="text-5xl" aria-hidden="true">☀</div>
        <h2 class="mt-10 text-3xl font-bold">Ready for the next hot day?</h2>
        <p class="mt-4 leading-7 text-orange-50">Keep contact details current, identify vulnerable household members, and record equipment that needs power or cooling.</p>
        <div class="mt-8 grid grid-cols-2 gap-3 text-sm"><div class="rounded-2xl bg-white/15 p-4">Household profile</div><div class="rounded-2xl bg-white/15 p-4">Sensitive equipment</div></div>
    </div>
</div>
<section class="mt-16 grid gap-5 md:grid-cols-3" aria-label="Explore HeatAlert">
    @foreach ([['Weather Alerts','Understand local heat conditions','weather-alerts'],['Outages','Find updates during power interruptions','outages'],['Cooling Points','Locate nearby spaces to cool down','cooling-points']] as [$heading,$copy,$route])
        <a href="{{ route($route) }}" class="rounded-2xl border border-orange-100 bg-white p-6 shadow-sm hover:border-orange-300"><h3 class="text-xl font-bold text-gray-900">{{ $heading }}</h3><p class="mt-3 text-gray-600">{{ $copy }}</p><span class="mt-5 inline-block font-semibold text-orange-700">Explore →</span></a>
    @endforeach
</section>
@endsection
