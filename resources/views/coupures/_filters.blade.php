@php
    $outages = !request()->routeIs('admin.signalements.index');
    $public = request()->routeIs('outages');
    $statuses = $outages ? \App\Models\Coupure::STATUTS : \App\Models\Signalement::STATUTS;
@endphp
<form method="GET" action="{{ url()->current() }}" class="ha-filter" data-auto-filter>
    <div class="ha-field ha-field--grow">
        <label class="ha-label" for="m2-search">Rechercher</label>
        <input class="ha-input" type="search" id="m2-search" name="q" value="{{ request('q') }}" maxlength="200" placeholder="{{ $outages ? 'Localisation ou description…' : 'Adresse ou description…' }}">
    </div>
    <div class="ha-field">
        <label class="ha-label" for="m2-status">Statut</label>
        <select class="ha-select" id="m2-status" name="statut">
            <option value="">Tous les statuts{{ $public ? ' visibles' : '' }}</option>
            @foreach($statuses as $status)<option value="{{ $status }}" @selected(request('statut') === $status)>{{ ucfirst($status) }}</option>@endforeach
        </select>
    </div>
    <div class="ha-field">
        <label class="ha-label" for="m2-sort">Tri</label>
        <select class="ha-select" id="m2-sort" name="sort">
            <option value="recent" @selected(request('sort', 'recent') === 'recent')>Plus récents</option>
            <option value="oldest" @selected(request('sort') === 'oldest')>Plus anciens</option>
        </select>
    </div>
    <button class="ha-btn ha-btn--secondary">Appliquer</button>
    <a class="ha-btn ha-btn--ghost" href="{{ url()->current() }}">Réinitialiser</a>
</form>
@if($public)<p class="mb-4">Seules les coupures actives sont visibles sur cette page.</p>@endif
