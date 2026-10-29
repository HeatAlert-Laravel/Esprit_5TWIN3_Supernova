@php
    $outages = !request()->routeIs('admin.signalements.index');
    $public = request()->routeIs('outages');
    $statuses = $outages ? \App\Models\Coupure::STATUTS : \App\Models\Signalement::STATUTS;
@endphp
<form method="GET" action="{{ $public ? route('outages') : url()->current() }}" class="ha-filter" data-auto-filter>
    @if($public)<input type="hidden" name="type" value="{{ request('type') }}">@endif
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="m2-search">Search</label>
        <input class="ha-input" type="search" id="m2-search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="{{ $outages ? 'Location or description…' : 'Address or description…' }}">
    </div>
    @unless($public)
        <div class="ha-field">
            <label class="ha-label" for="m2-status">Status</label>
            <select class="ha-select" id="m2-status" name="statut">
                <option value="">{{ __('All statuses') }}</option>
                @foreach($statuses as $status)<option value="{{ $status }}" @selected(request('statut') === $status)>{{ __($status) }}</option>@endforeach
            </select>
        </div>
    @endunless
    <div class="ha-field">
        <label class="ha-label" for="m2-sort">Sort</label>
        <select class="ha-select" id="m2-sort" name="sort">
            <option value="recent" @selected(request('sort', 'recent') === 'recent')>Newest first</option>
            <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
        </select>
    </div>
    <button class="ha-btn ha-btn--secondary">Apply</button>
    <a class="ha-btn ha-btn--ghost" href="{{ url()->current() }}">Reset</a>
</form>
@if($public)<p class="mb-4">Only active outages are shown on this page.</p>@endif
