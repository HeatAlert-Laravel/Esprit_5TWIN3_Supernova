@extends('layouts.app')
@section('title', $categorieConseil->nom)
@section('content')
<x-ha.page-header :title="$categorieConseil->nom" :description="$categorieConseil->description" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice categories'), route('admin.categorie-conseils.index')], [$categorieConseil->nom, null]]">
    <x-slot:actions><a href="{{ route('admin.categorie-conseils.edit', $categorieConseil) }}" class="ha-btn ha-btn--outline"><x-ha.icon name="pencil" size="sm" />{{ __('Edit category') }}</a><a href="{{ route('admin.conseils.create', ['category' => $categorieConseil->id]) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />{{ __('Add advice') }}</a></x-slot:actions>
</x-ha.page-header>
@if($conseils->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('No advice in this category yet')" :description="__('Add an article to help residents prepare.')" /></div>
@else
    <div class="ha-table-wrap"><table class="ha-table">
        <thead><tr><th scope="col">{{ __('Article title') }}</th><th scope="col">{{ __('Audience') }}</th><th scope="col">{{ __('Visibility') }}</th><th scope="col" class="ha-actions-cell"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
        <tbody>@foreach($conseils as $conseil)<tr>
            <td><a href="{{ route('admin.conseils.show', $conseil) }}" class="ha-cell-person__name">{{ $conseil->titre }}</a></td><td>{{ $conseil->audienceLabel() }}</td>
            <td><x-ha.badge :variant="$conseil->actif ? 'success' : 'neutral'">{{ $conseil->actif ? __('Published') : __('Draft') }}</x-ha.badge></td>
            <td class="ha-actions-cell"><a href="{{ route('admin.conseils.edit', $conseil) }}" class="ha-btn ha-btn--ghost ha-btn--sm" aria-label="{{ __('Edit :name', ['name' => $conseil->titre]) }}">{{ __('Edit') }}</a></td>
        </tr>@endforeach</tbody>
    </table></div><div class="ha-pagination">{{ $conseils->links() }}</div>
@endif
<div class="ha-form-actions">
    @if($conseils->total() === 0)
        <form method="POST" action="{{ route('admin.categorie-conseils.destroy', $categorieConseil) }}" data-confirm="{{ __('Delete this empty category?') }}" data-confirm-subject="{{ $categorieConseil->nom }}">
            @csrf @method('DELETE')<button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />{{ __('Delete category') }}</button>
        </form>
    @else
        <p class="ha-help">{{ __('Move or delete the articles before deleting this category.') }}</p>
    @endif
</div>
@endsection
