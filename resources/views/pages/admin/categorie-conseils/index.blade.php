@extends('layouts.app')
@section('title', __('Advice categories'))
@section('content')
<x-ha.page-header :title="__('Advice categories')" :description="__('Give every advice article a clear home.')" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice categories'), null]]">
    <x-slot:actions><a href="{{ route('admin.conseils.index') }}" class="ha-btn ha-btn--outline">{{ __('All advice') }}</a><a href="{{ route('admin.categorie-conseils.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />{{ __('Add category') }}</a></x-slot:actions>
</x-ha.page-header>
<form method="GET" action="{{ route('admin.categorie-conseils.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="{{ __('Filter advice categories') }}">
    <div class="ha-field ha-field--grow"><label class="ha-label" for="q">{{ __('Search') }}</label><input id="q" name="q" type="search" maxlength="100" class="ha-input" value="{{ $search }}" placeholder="{{ __('Search categories...') }}"></div>
    <div class="ha-filter__actions"><noscript><button class="ha-btn ha-btn--secondary">{{ __('Apply filters') }}</button></noscript>@if($search !== '')<a href="{{ route('admin.categorie-conseils.index') }}" class="ha-btn ha-btn--ghost">{{ __('Reset filters') }}</a>@endif</div>
</form>
@if($categories->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('No categories found')" :description="__('Try another search or create your first advice category.')" /></div>
@else
    <div class="ha-table-wrap"><table class="ha-table">
        <thead><tr><th scope="col">{{ __('Category') }}</th><th scope="col">{{ __('Description') }}</th><th scope="col" class="ha-num">{{ __('Articles') }}</th><th scope="col" class="ha-num">{{ __('Published') }}</th><th scope="col" class="ha-actions-cell"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
        <tbody>@foreach($categories as $category)<tr>
            <td><div class="ha-cell-person"><span class="ha-icon-chip"><x-ha.icon :name="$category->icone" /></span><a href="{{ route('admin.categorie-conseils.show', $category) }}" class="ha-cell-person__name">{{ $category->nom }}</a></div></td>
            <td>{{ \Illuminate\Support\Str::limit($category->description, 100) ?: '—' }}</td>
            <td class="ha-num">{{ $category->conseils_count }}</td><td class="ha-num">{{ $category->published_count }}</td>
            <td class="ha-actions-cell"><a href="{{ route('admin.categorie-conseils.show', $category) }}" class="ha-btn ha-btn--outline ha-btn--sm" aria-label="{{ __('View :name', ['name' => $category->nom]) }}">{{ __('View') }}</a><a href="{{ route('admin.categorie-conseils.edit', $category) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="{{ __('Edit :name', ['name' => $category->nom]) }}">{{ __('Edit') }}</a></td>
        </tr>@endforeach</tbody>
    </table></div>
    <div class="ha-pagination">{{ $categories->links() }}</div>
@endif
@endsection
