@extends('layouts.app')
@section('title', $conseil->titre)
@section('content')
<x-ha.page-header :title="$conseil->titre" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice'), route('admin.conseils.index')], [$conseil->titre, null]]">
    <x-slot:badges><x-ha.badge :variant="$conseil->actif ? 'success' : 'neutral'">{{ $conseil->actif ? __('Published') : __('Draft') }}</x-ha.badge></x-slot:badges>
    <x-slot:actions><a class="ha-btn ha-btn--outline" href="{{ rtrim(config('app.frontoffice_url'), '/') }}/advice/{{ $conseil->id }}/preview" target="_blank" rel="noopener"><x-ha.icon name="eye" size="sm" />{{ __('Preview as resident') }}<span class="sr-only">{{ __('(opens in a new tab)') }}</span></a><a class="ha-btn ha-btn--primary" href="{{ route('admin.conseils.edit', $conseil) }}"><x-ha.icon name="pencil" size="sm" />{{ __('Edit advice') }}</a></x-slot:actions>
</x-ha.page-header>
<div class="ha-grid ha-grid--main">
    <article class="ha-card ha-advice-article">
        <p class="ha-advice-summary">{{ $conseil->resume }}</p>
        <div class="ha-advice-body">@foreach($conseil->paragraphs() as $paragraph)<p>{{ $paragraph }}</p>@endforeach</div>
    </article>
    <aside class="ha-stack">
        <section class="ha-card">
            <h2 class="ha-card__title">{{ __('Article details') }}</h2>
            <dl class="ha-dl ha-advice-details">
                <div><dt>{{ __('Category') }}</dt><dd><a href="{{ route('admin.categorie-conseils.show', $conseil->categorieConseil) }}">{{ $conseil->categorieConseil->nom }}</a></dd></div>
                <div><dt>{{ __('Audience') }}</dt><dd>{{ $conseil->audienceLabel() }}</dd></div>
                <div><dt>{{ __('Situation') }}</dt><dd>{{ $conseil->situationLabel() }}</dd></div>
                <div><dt>{{ __('Last updated') }}</dt><dd>{{ $conseil->updated_at->format('d M Y') }}</dd></div>
            </dl>
        </section>
        <section class="ha-card ha-stack">
            <h2 class="ha-card__title">{{ __('Visibility') }}</h2>
            <p class="ha-help">{{ $conseil->actif ? __('Residents can read this article on the advice page.') : __('This draft is visible only to administrators.') }}</p>
            <form method="POST" action="{{ route('admin.conseils.publication', $conseil) }}">
                @csrf @method('PATCH')<input type="hidden" name="actif" value="{{ $conseil->actif ? 0 : 1 }}">
                <button class="ha-btn {{ $conseil->actif ? 'ha-btn--outline' : 'ha-btn--primary' }}">{{ $conseil->actif ? __('Move to draft') : __('Publish advice') }}</button>
            </form>
        </section>
    </aside>
</div>
<div class="ha-form-actions"><form method="POST" action="{{ route('admin.conseils.destroy', $conseil) }}" data-confirm="{{ __('Delete this advice article?') }}" data-confirm-subject="{{ $conseil->titre }}">
    @csrf @method('DELETE')<button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />{{ __('Delete advice') }}</button>
</form></div>
@endsection
