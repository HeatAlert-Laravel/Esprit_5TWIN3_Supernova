@extends('layouts.app')
@section('title', 'Point type: ' . $type->nom)
@section('content')
@php($count = $type->pointFraicheurs->count())
<x-ha.page-header :title="$type->nom" :description="$type->description ?? 'Point type details.'" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Point types', route('admin.type-points.index')], [$type->nom, null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $count }} {{ \Illuminate\Support\Str::plural('point', $count) }}</span></x-slot:badges>
    <x-slot:actions>
        <a href="{{ route('admin.type-points.edit', $type) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.type-points.destroy', $type) }}" onsubmit="return confirm('Delete this point type?')">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <section class="ha-card" aria-labelledby="type-info">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="type-info">
                <span class="ha-icon-chip"><x-cooling-icon :name="$type->icone ?? 'sun'" /></span>
                Type information
            </h2>
        </div>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Name</dt><dd class="font-medium">{{ $type->nom }}</dd></div>
            <div>
                <dt>Icon</dt>
                <dd class="flex items-center gap-1.5">
                    @if($type->icone)
                        <span class="ha-icon-chip"><x-cooling-icon :name="$type->icone" /></span>
                        <span class="ha-mono text-sm">{{ $type->icone }}</span>
                    @else
                        <span class="text-gray-400">None</span>
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2"><dt>Description</dt><dd class="text-gray-600 dark:text-gray-300">{{ $type->description ?? 'No description provided.' }}</dd></div>
        </dl>
        <x-ha.divider>Record metadata</x-ha.divider>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Created at</dt><dd class="ha-mono">{{ $type->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div><dt>Last updated</dt><dd class="ha-mono">{{ $type->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="ha-card" aria-labelledby="type-points">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="type-points">
                <span class="ha-icon-chip"><x-ha.icon name="snowflake" /></span>
                Cooling points of this type <span class="ha-count-pill">{{ $count }}</span>
            </h2>
        </div>
        {{-- $type->pointFraicheurs: hasMany relation --}}
        @forelse($type->pointFraicheurs as $point)
            @if($loop->first)<ul class="ha-list">@endif
            <li>
                <div class="min-w-0">
                    <a class="ha-cell-person__name" href="{{ route('admin.point-fraicheurs.show', $point) }}">{{ $point->nom }}</a>
                    <span class="ha-cell-person__sub">{{ $point->adresse }} · {{ $point->quartier?->nom ?? 'No neighborhood' }}</span>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <span class="ha-badge {{ $point->actif ? 'ha-badge--success' : 'ha-badge--neutral' }}">{{ $point->actif ? 'Active' : 'Inactive' }}</span>
                </div>
            </li>
            @if($loop->last)</ul>@endif
        @empty
            <x-ha.empty-state icon="snowflake" title="No cooling points for this type" description="This type is not currently assigned to any cooling point. It can be safely deleted." />
        @endforelse
    </section>
</div>
@endsection
