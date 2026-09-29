@extends('layouts.front')
@section('title', 'My Profile')
@section('content')
<div class="mb-8">
    <span class="text-sm font-bold uppercase tracking-widest text-orange-600">Resident area</span>
    <h1 class="mt-2 text-4xl font-bold">My Profile</h1>
    <p class="mt-3 text-gray-600">Keep your household information ready for local heat planning.</p>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-xl font-bold">Household details</h2>
        <form method="POST" action="{{ route('my-profile.update') }}" class="mt-5 space-y-4">
            @csrf
            @method('PUT')
            @foreach ([['phone', 'Phone'], ['address', 'Address'], ['neighborhood', 'Neighborhood']] as [$field, $label])
                <div>
                    <label class="block font-medium" for="{{ $field }}">{{ $label }}</label>
                    <input class="mt-2 w-full rounded-lg border border-gray-300 p-3" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $profile?->$field) }}" required>
                    @error($field)<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <input type="hidden" name="has_fragile_person" value="0">
            <label class="flex items-center gap-2"><input type="checkbox" name="has_fragile_person" value="1" {{ old('has_fragile_person', $profile?->has_fragile_person) ? 'checked' : '' }}>A fragile person lives in this household</label>
            @error('has_fragile_person')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
            <button class="rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">Save profile</button>
        </form>
    </section>

    <section class="rounded-2xl bg-white p-6 shadow-sm">
        <h2 class="text-xl font-bold">Sensitive equipment</h2>
        <p class="mt-2 text-gray-600">Equipment linked to your household profile.</p>

        @if($profile)
            <ul class="mt-5 divide-y divide-gray-100">
                @forelse($profile->sensitiveEquipments as $item)
                    <li class="py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $item->name }}</h3>
                                <p class="mt-1 text-sm text-gray-600">Type: {{ $item->type }} · Priority: {{ ucfirst($item->priority_level) }}</p>
                                @if($item->description)<p class="mt-2 text-sm text-gray-600">{{ $item->description }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('profile.equipment.edit', $item) }}" class="rounded-lg border border-orange-300 px-3 py-2 text-sm font-semibold text-orange-800 hover:bg-orange-50">Edit</a>
                                <form method="POST" action="{{ route('profile.equipment.destroy', $item) }}" onsubmit="return confirm('Delete this equipment?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-lg border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Delete</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-5 text-gray-500">No equipment recorded yet.</li>
                @endforelse
            </ul>
            <a href="{{ route('profile.equipment.create') }}" class="mt-5 inline-flex rounded-lg bg-orange-600 px-5 py-3 font-semibold text-white hover:bg-orange-700">+ Add equipment</a>
        @else
            <p class="mt-5 rounded-lg bg-orange-50 p-4 text-sm text-orange-900">Save your household details first to add sensitive equipment.</p>
        @endif
    </section>
</div>
@endsection
