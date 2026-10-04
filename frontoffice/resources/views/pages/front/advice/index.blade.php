@extends('layouts.front')
@section('title', $onlySaved ? __('My saved advice') : __('Advice'))
@section('content')
<x-ha.page-header :title="$onlySaved ? __('My saved advice') : __('A little preparation goes a long way.')" :eyebrow="__('Practical advice')" :description="$onlySaved ? __('Your useful articles, kept together for later.') : __('Simple steps to help you, your family, and your home through hot days and power outages.')">
    <x-slot:actions><a href="{{ route($onlySaved ? 'advice' : 'advice.saved') }}" class="ha-btn ha-btn--outline"><x-ha.icon :name="$onlySaved ? 'lightbulb' : 'bookmark'" size="sm" />{{ $onlySaved ? __('Browse all advice') : __('My saved advice') }}</a></x-slot:actions>
</x-ha.page-header>
<nav class="ha-advice-situations" aria-label="{{ __('Choose a situation') }}">
    @foreach(['' => __('All advice'), 'heatwave' => __('During a heatwave'), 'outage' => __('During a power outage')] as $value => $label)
        <a href="{{ route($indexRoute, array_merge(request()->except('page', 'situation'), $value ? ['situation' => $value] : [])) }}" class="ha-btn {{ $situation === $value ? 'ha-btn--primary' : 'ha-btn--outline' }}" @if($situation === $value) aria-current="page" @endif><x-ha.icon :name="match($value) {'heatwave' => 'sun', 'outage' => 'zap', default => 'lightbulb'}" size="sm" />{{ $label }}</a>
    @endforeach
</nav>
<form method="GET" action="{{ route($indexRoute) }}" class="ha-filter" data-auto-filter role="search" aria-label="{{ __('Filter advice') }}">
    @if($situation !== '')<input type="hidden" name="situation" value="{{ $situation }}">@endif
    <div class="ha-field ha-field--grow"><label class="ha-label" for="q">{{ __('Search') }}</label><input id="q" name="q" type="search" class="ha-input" maxlength="100" value="{{ $search }}" placeholder="{{ __('What would you like help with?') }}"></div>
    <div class="ha-field"><label class="ha-label" for="category">{{ __('Category') }}</label><select id="category" name="category" class="ha-select"><option value="">{{ __('All categories') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->nom }}</option>@endforeach</select></div>
    <div class="ha-field"><label class="ha-label" for="audience">{{ __('Who is it for?') }}</label><select id="audience" name="audience" class="ha-select"><option value="">{{ __('All audiences') }}</option>@foreach(\App\Models\Conseil::AUDIENCES as $value => $label)<option value="{{ $value }}" @selected($audience === $value)>{{ __($label) }}</option>@endforeach</select></div>
    <div class="ha-filter__actions"><noscript><button class="ha-btn ha-btn--secondary">{{ __('Apply filters') }}</button></noscript>@if($search !== '' || $categoryId !== '' || $audience !== '' || $situation !== '')<a href="{{ route($indexRoute) }}" class="ha-btn ha-btn--ghost">{{ __('Reset filters') }}</a>@endif</div>
</form>
@if($audience !== '')<p class="ha-help ha-advice-filter-note">{{ __('Also includes advice for everyone.') }}</p>@endif
<p class="ha-advice-results" role="status">{{ $conseils->total() === 1 ? __('1 article') : __(':count articles', ['count' => $conseils->total()]) }}</p>
@if($conseils->isEmpty())
    <div class="ha-card"><x-ha.empty-state :icon="$onlySaved ? 'bookmark' : 'lightbulb'" :title="$onlySaved && $search === '' && $categoryId === '' && $audience === '' && $situation === '' ? __('No saved advice yet') : __('No advice found just yet')" :description="$onlySaved ? __('Save useful articles while browsing. They will appear here when published.') : __('Try another search or reset your filters to explore all available advice.')"><a class="ha-btn ha-btn--outline" href="{{ route('advice') }}">{{ __('Explore all advice') }}</a></x-ha.empty-state></div>
@else
    <div class="ha-stack">
        @foreach($conseils->getCollection()->groupBy('categorie_conseil_id') as $group)
            @php($category = $group->first()->categorieConseil)
            <section aria-labelledby="category-{{ $category->id }}">
                <div class="ha-advice-category-head"><span class="ha-icon-chip"><x-ha.icon :name="$category->icone" /></span><div><h2 id="category-{{ $category->id }}">{{ $category->nom }}</h2>@if($category->description)<p>{{ $category->description }}</p>@endif</div></div>
                <div class="ha-grid ha-grid--3">@foreach($group as $conseil)<x-advice.card :conseil="$conseil" :return-to="$returnTo" />@endforeach</div>
            </section>
        @endforeach
    </div>
    <div class="ha-pagination">{{ $conseils->links() }}</div>
@endif
@endsection
