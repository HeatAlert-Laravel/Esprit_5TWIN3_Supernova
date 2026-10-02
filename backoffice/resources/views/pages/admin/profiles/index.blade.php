@extends('layouts.app')
@section('title', 'Profiles')
@section('content')
@php($isFiltered = $filters['q'] !== '' || $filters['neighborhood'] !== '')
<x-ha.page-header title="Profiles" description="Household profiles registered by residents, with their contact details and linked equipment." :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Profiles', null]]">
    <x-slot:badges><span class="ha-count-pill">{{ $isFiltered ? $profiles->total().' of '.$totalProfiles : $totalProfiles }} {{ \Illuminate\Support\Str::plural('record', $isFiltered ? $profiles->total() : $totalProfiles) }}</span></x-slot:badges>
    <x-slot:actions><a href="{{ route('admin.profiles.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add profile</a></x-slot:actions>
</x-ha.page-header>

<form method="GET" action="{{ route('admin.profiles.index') }}" class="ha-filter" role="search" aria-label="Filter profiles">
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="q">Resident</label>
        <input class="ha-input" id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="Search by name or email">
    </div>
    <div class="ha-field">
        <label class="ha-label" for="neighborhood-filter">Neighborhood</label>
        <select class="ha-select" id="neighborhood-filter" name="neighborhood">
            <option value="">All neighborhoods</option>
            @foreach($neighborhoods as $neighborhood)<option value="{{ $neighborhood }}" @selected($filters['neighborhood'] === $neighborhood)>{{ $neighborhood }}</option>@endforeach
        </select>
    </div>
    <div class="ha-filter__actions">
        <button class="ha-btn ha-btn--secondary"><x-ha.icon name="search" size="sm" />Filter</button>
        @if($isFiltered)<a href="{{ route('admin.profiles.index') }}" class="ha-btn ha-btn--ghost">Reset</a>@endif
    </div>
</form>

@if($profiles->isEmpty())
    <div class="ha-card">
        @if($isFiltered)
            <x-ha.empty-state icon="search" title="No profiles match these filters" description="Try a different name, email or neighborhood.">
                <a href="{{ route('admin.profiles.index') }}" class="ha-btn ha-btn--outline">Reset filters</a>
            </x-ha.empty-state>
        @else
            <x-ha.empty-state icon="users" title="No profiles yet" description="Profiles appear here once residents save their household details, or when you add one yourself.">
                <a href="{{ route('admin.profiles.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />Add profile</a>
            </x-ha.empty-state>
        @endif
    </div>
@else
    <div class="ha-table-wrap">
        <table class="ha-table">
            <thead>
                <tr>
                    <th scope="col">Resident</th>
                    <th scope="col">Neighborhood</th>
                    <th scope="col">Details</th>
                    <th scope="col" class="ha-num">Equipment</th>
                    <th scope="col">Updated</th>
                    <th scope="col" class="ha-actions-cell"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach($profiles as $profile)
                    <tr>
                        <td>
                            <div class="ha-cell-person">
                                <x-ha.avatar :name="$profile->user->name" />
                                <div>
                                    <a href="{{ route('admin.profiles.show', $profile) }}" class="ha-cell-person__name">{{ $profile->user->name }}</a>
                                    <span class="ha-cell-person__sub">{{ $profile->user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>@if($profile->neighborhood)<span class="ha-tag">{{ $profile->neighborhood }}</span>@else<span class="ha-muted">Not provided</span>@endif</td>
                        <td>
                            <div class="ha-reasons">
                                <x-ha.badge :variant="$profile->isComplete() ? 'success' : 'warning'">{{ $profile->isComplete() ? 'Complete' : 'Incomplete' }}</x-ha.badge>
                                @if($profile->has_fragile_person)<x-ha.badge variant="info">Fragile person</x-ha.badge>@endif
                            </div>
                        </td>
                        <td class="ha-num">{{ $profile->sensitive_equipments_count }}</td>
                        <td><time class="ha-mono" datetime="{{ $profile->updated_at?->toDateString() }}">{{ $profile->updated_at?->format('d M Y') ?? '—' }}</time></td>
                        <td class="ha-actions-cell">
                            <a href="{{ route('admin.profiles.show', $profile) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="View {{ $profile->user->name }}"><x-ha.icon name="eye" size="sm" />View</a>
                            <a href="{{ route('admin.profiles.edit', $profile) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="Edit {{ $profile->user->name }}"><x-ha.icon name="pencil" size="sm" />Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="ha-pagination">{{ $profiles->links() }}</div>
@endif
@endsection
