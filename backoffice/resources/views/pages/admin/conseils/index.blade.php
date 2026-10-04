@extends('layouts.app')
@section('title', __('Advice'))
@section('content')
<x-ha.page-header :title="__('Advice')" :description="__('Practical guidance for heatwaves and power outages. Draft, review, then publish.')" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice'), null]]">
    <x-slot:actions><a href="{{ route('admin.categorie-conseils.index') }}" class="ha-btn ha-btn--outline">{{ __('Manage categories') }}</a><a href="{{ route('admin.conseils.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />{{ __('Add advice') }}</a></x-slot:actions>
</x-ha.page-header>
<form method="GET" action="{{ route('admin.conseils.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="{{ __('Filter advice') }}">
    <div class="ha-field ha-field--grow"><label class="ha-label" for="q">{{ __('Search') }}</label><input id="q" name="q" type="search" class="ha-input" maxlength="100" value="{{ $search }}" placeholder="{{ __('Search advice...') }}"></div>
    <div class="ha-field"><label class="ha-label" for="category">{{ __('Category') }}</label><select id="category" name="category" class="ha-select"><option value="">{{ __('All categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->nom }}</option>@endforeach</select></div>
    <div class="ha-field"><label class="ha-label" for="status">{{ __('Visibility') }}</label><select id="status" name="status" class="ha-select"><option value="">{{ __('All statuses') }}</option><option value="published" @selected($status === 'published')>{{ __('Published') }}</option><option value="draft" @selected($status === 'draft')>{{ __('Draft') }}</option></select></div>
    <div class="ha-filter__actions"><noscript><button class="ha-btn ha-btn--secondary">{{ __('Apply filters') }}</button></noscript>@if($search !== '' || $categoryId !== '' || $status !== '')<a class="ha-btn ha-btn--ghost" href="{{ route('admin.conseils.index') }}">{{ __('Reset filters') }}</a>@endif</div>
</form>
@if($conseils->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('No advice found')" :description="__('Try different filters or add a new article.')" /></div>
@else
    <div class="ha-table-wrap"><table class="ha-table ha-advice-table">
        <thead><tr><th scope="col">{{ __('Article title') }}</th><th scope="col">{{ __('Category') }}</th><th scope="col">{{ __('Audience') }}</th><th scope="col">{{ __('Situation') }}</th><th scope="col">{{ __('Visibility') }}</th><th scope="col" class="ha-actions-cell"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
        <tbody>@foreach($conseils as $conseil)<tr>
            <td><a href="{{ route('admin.conseils.show', $conseil) }}" class="ha-cell-person__name">{{ $conseil->titre }}</a><span class="ha-cell-person__sub">{{ \Illuminate\Support\Str::limit($conseil->resume, 70) }}</span></td>
            <td><a href="{{ route('admin.categorie-conseils.show', $conseil->categorieConseil) }}">{{ $conseil->categorieConseil->nom }}</a></td>
            <td>{{ $conseil->audienceLabel() }}</td><td>{{ $conseil->situationLabel() }}</td>
            <td><x-ha.badge :variant="$conseil->actif ? 'success' : 'neutral'">{{ $conseil->actif ? __('Published') : __('Draft') }}</x-ha.badge></td>
            <td class="ha-actions-cell"><a class="ha-btn ha-btn--outline ha-btn--sm" href="{{ route('admin.conseils.show', $conseil) }}" aria-label="{{ __('View :name', ['name' => $conseil->titre]) }}">{{ __('View') }}</a><a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.conseils.edit', $conseil) }}" aria-label="{{ __('Edit :name', ['name' => $conseil->titre]) }}">{{ __('Edit') }}</a></td>
        </tr>@endforeach</tbody>
    </table></div><div class="ha-pagination">{{ $conseils->links() }}</div>
@endif
@endsection
