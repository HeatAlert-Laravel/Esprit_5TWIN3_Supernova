@extends('layouts.admin')
@section('title', __('Advice'))
@section('content')
<x-ha.page-header :title="__('Advice')" :description="__('Practical guidance for heatwaves and power outages. Draft, review, then publish.')" :breadcrumbs="[[__('Dashboard'), route('admin.dashboard')], [__('Advice'), null]]">
    <x-slot:actions><a href="{{ route('admin.categorie-conseils.index') }}" class="ha-btn ha-btn--outline">{{ __('Manage categories') }}</a><a href="{{ route('admin.conseils.create') }}" class="ha-btn ha-btn--primary"><x-ha.icon name="plus" size="sm" />{{ __('Add advice') }}</a></x-slot:actions>
</x-ha.page-header>
<form method="GET" action="{{ route('admin.conseils.index') }}" class="ha-filter" data-auto-filter role="search" aria-label="{{ __('Filter advice') }}">
    <div class="ha-field ha-field--grow"><label class="ha-label" for="q">{{ __('Search') }}</label><input id="q" name="q" type="search" class="ha-input" maxlength="100" value="{{ $search }}" placeholder="{{ __('Search advice...') }}"></div>
    <div class="ha-field"><label class="ha-label" for="category">{{ __('Category') }}</label><select id="category" name="category" class="ha-select"><option value="">{{ __('All categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->nom }}</option>@endforeach</select></div>
    <div class="ha-field"><label class="ha-label" for="status">{{ __('Visibility') }}</label><select id="status" name="status" class="ha-select"><option value="">{{ __('All statuses') }}</option><option value="published" @selected($status === 'published')>{{ __('Published') }}</option><option value="draft" @selected($status === 'draft')>{{ __('Draft') }}</option></select></div>
    <div class="ha-field"><label class="ha-label" for="feedback">{{ __('Reader feedback') }}</label><select id="feedback" name="feedback" class="ha-select"><option value="">{{ __('All feedback') }}</option><option value="helpful" @selected($feedback === 'helpful')>{{ __('Most helpful') }}</option><option value="downvotes" @selected($feedback === 'downvotes')>{{ __('Received downvotes') }}</option><option value="unrated" @selected($feedback === 'unrated')>{{ __('No feedback yet') }}</option></select></div>
    <div class="ha-filter__actions"><noscript><button class="ha-btn ha-btn--secondary">{{ __('Apply filters') }}</button></noscript>@if($search !== '' || $categoryId !== '' || $status !== '' || $feedback !== '')<a class="ha-btn ha-btn--ghost" href="{{ route('admin.conseils.index') }}">{{ __('Reset filters') }}</a>@endif</div>
</form>
<details class="ha-card ha-feedback-overview">
    <summary>{{ __('Reader feedback overview') }}</summary>
    <p class="ha-help">{{ __('Published articles matching your search, category and visibility filters. Votes are reader opinions, not a quality verdict.') }}</p>
    <div class="ha-grid ha-grid--2">
        <section aria-labelledby="helpful-heading"><h2 id="helpful-heading">{{ __('Most helpful') }}</h2>
            <ul class="ha-feedback-list">@forelse($mostHelpful as $article)<li><a href="{{ route('admin.conseils.show', $article) }}">{{ $article->titre }}</a><span class="ha-help">{{ __('Score :score', ['score' => $article->voteScore()]) }}</span></li>@empty<li class="ha-help">{{ __('No positive feedback yet.') }}</li>@endforelse</ul>
        </section>
        <section aria-labelledby="review-heading"><h2 id="review-heading">{{ __('Worth reviewing') }}</h2>
            <ul class="ha-feedback-list">@forelse($reviewFeedback as $article)<li><a href="{{ route('admin.conseils.show', $article) }}">{{ $article->titre }}</a><span class="ha-help">{{ __(':up upvotes · :down downvotes', ['up' => $article->upvotes_count, 'down' => $article->downvotes_count]) }}</span></li>@empty<li class="ha-help">{{ __('No downvotes on these articles.') }}</li>@endforelse</ul>
        </section>
    </div>
</details>
@if($conseils->isEmpty())
    <div class="ha-card"><x-ha.empty-state icon="lightbulb" :title="__('No advice found')" :description="__('Try different filters or add a new article.')" /></div>
@else
    <div class="ha-table-wrap"><table class="ha-table ha-advice-table">
        <thead><tr><th scope="col">{{ __('Article title') }}</th><th scope="col">{{ __('Category') }}</th><th scope="col">{{ __('Audience') }}</th><th scope="col">{{ __('Situation') }}</th><th scope="col">{{ __('Visibility') }}</th><th scope="col">{{ __('Reader feedback') }}</th><th scope="col" class="ha-actions-cell"><span class="sr-only">{{ __('Actions') }}</span></th></tr></thead>
        <tbody>@foreach($conseils as $conseil)<tr>
            <td><a href="{{ route('admin.conseils.show', $conseil) }}" class="ha-cell-person__name">{{ $conseil->titre }}</a><span class="ha-cell-person__sub">{{ \Illuminate\Support\Str::limit($conseil->resume, 70) }}</span></td>
            <td><a href="{{ route('admin.categorie-conseils.show', $conseil->categorieConseil) }}">{{ $conseil->categorieConseil->nom }}</a></td>
            <td>{{ $conseil->audienceLabel() }}</td><td>{{ $conseil->situationLabel() }}</td>
            <td><x-ha.badge :variant="$conseil->actif ? 'success' : 'neutral'">{{ $conseil->actif ? __('Published') : __('Draft') }}</x-ha.badge></td>
            <td>@if($conseil->upvotes_count + $conseil->downvotes_count > 0)<strong>{{ __('Score :score', ['score' => $conseil->voteScore()]) }}</strong><span class="ha-cell-person__sub">{{ __(':up upvotes · :down downvotes', ['up' => $conseil->upvotes_count, 'down' => $conseil->downvotes_count]) }}</span>@else<span class="ha-help">{{ __('No feedback yet') }}</span>@endif</td>
            <td class="ha-actions-cell"><a class="ha-btn ha-btn--outline ha-btn--sm" href="{{ route('admin.conseils.show', $conseil) }}" aria-label="{{ __('View :name', ['name' => $conseil->titre]) }}">{{ __('View') }}</a><a class="ha-btn ha-btn--ghost ha-btn--sm" href="{{ route('admin.conseils.edit', $conseil) }}" aria-label="{{ __('Edit :name', ['name' => $conseil->titre]) }}">{{ __('Edit') }}</a></td>
        </tr>@endforeach</tbody>
    </table></div><div class="ha-pagination">{{ $conseils->links() }}</div>
@endif
@endsection
