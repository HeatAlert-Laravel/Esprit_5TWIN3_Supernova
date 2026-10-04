@extends('layouts.front')
@section('title', 'Cooling points')

@section('content')
{{-- Leaflet Assets for Interactive Map --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
/* Scoped Clean Styling matching HeatAlert Theme Tokens */
.ha-cp-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Header */
.ha-cp-header {
    margin-bottom: 4px;
}
.ha-cp-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--ha-ink);
    letter-spacing: -0.02em;
    margin: 0 0 6px 0;
}
.ha-cp-subtitle {
    font-size: 0.9375rem;
    color: var(--ha-ink-2);
    margin: 0;
}

/* Search & Quick Filters Box */
.ha-search-box {
    background: var(--ha-surface);
    border: 1px solid var(--ha-border);
    border-radius: var(--ha-r-lg);
    padding: 16px;
    box-shadow: var(--ha-shadow-rest);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.ha-search-top-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.ha-search-input-wrap {
    position: relative;
    flex: 1;
}

.ha-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ha-ink-3);
    pointer-events: none;
    display: flex;
    align-items: center;
}

.ha-search-input {
    width: 100%;
    height: 46px;
    padding: 0 16px 0 42px;
    font-size: 0.9375rem;
    border: 1px solid var(--ha-border-strong);
    border-radius: var(--ha-r-md);
    background: var(--ha-surface);
    color: var(--ha-ink);
    outline: none;
    transition: border-color .15s ease, box-shadow .15s ease;
}

.ha-search-input:focus {
    border-color: var(--ha-info);
    box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.12);
}

.ha-loc-btn {
    height: 46px;
    padding: 0 16px;
    background: var(--ha-surface-2);
    border: 1px solid var(--ha-border-strong);
    border-radius: var(--ha-r-md);
    color: var(--ha-ink);
    font-size: 0.875rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color .15s ease, border-color .15s ease;
}

.ha-loc-btn:hover {
    background: var(--ha-shade-100);
    border-color: var(--ha-shade-600);
    color: var(--ha-shade-800);
}

.ha-loc-btn.is-active {
    background: var(--ha-shade-600);
    border-color: var(--ha-shade-600);
    color: #fff;
}

/* Filter Controls Row */
.ha-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--ha-border);
}

.ha-chips-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 2px;
    -webkit-overflow-scrolling: touch;
}

.ha-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.8125rem;
    font-weight: 500;
    background: var(--ha-surface-2);
    border: 1px solid transparent;
    color: var(--ha-ink-2);
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: all .15s ease;
}

.ha-chip:hover {
    background: var(--ha-border);
    color: var(--ha-ink);
}

.ha-chip.is-active {
    background: var(--ha-ink);
    color: #fff;
    font-weight: 600;
}

.ha-selects-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: auto;
}

.ha-compact-select {
    height: 32px;
    padding: 0 28px 0 10px;
    font-size: 0.8125rem;
    border-radius: var(--ha-r-md);
    border: 1px solid var(--ha-border-strong);
    background: var(--ha-surface);
    color: var(--ha-ink);
    cursor: pointer;
}

/* Results Count Bar */
.ha-results-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8125rem;
    color: var(--ha-ink-3);
    padding: 0 4px;
}

.ha-results-count {
    color: var(--ha-ink-2);
    font-weight: 600;
}

/* Two-column layout on Desktop */
.ha-cooling-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}

.ha-cooling-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Cooling Point Card */
.ha-point-card {
    background: var(--ha-surface);
    border: 1px solid var(--ha-border);
    border-radius: var(--ha-r-lg);
    padding: 18px;
    box-shadow: var(--ha-shadow-rest);
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.ha-point-card:hover {
    box-shadow: var(--ha-shadow-hover);
    border-color: var(--ha-border-strong);
}

.ha-point-card.is-selected {
    border-color: var(--ha-info);
    box-shadow: 0 0 0 2px var(--ha-info);
}

/* Card Header */
.ha-card-head-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.ha-point-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--ha-ink);
    margin: 0;
    line-height: 1.3;
}

.ha-status-block {
    text-align: right;
    flex-shrink: 0;
}

.ha-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    padding: 2px 8px;
    border-radius: 999px;
    white-space: nowrap;
}

.ha-status-pill--open {
    background: var(--ha-success-bg);
    color: var(--ha-success);
}

.ha-status-pill--closed {
    background: var(--ha-error-bg);
    color: var(--ha-error);
}

.ha-status-sub {
    display: block;
    font-size: 0.75rem;
    color: var(--ha-ink-3);
    margin-top: 2px;
    font-family: var(--ha-font-mono);
}

/* Sub-row: Category Badge */
.ha-card-sub-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Location and Distance */
.ha-location-line {
    font-size: 0.8125rem;
    color: var(--ha-ink-2);
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 0;
}

.ha-dist-badge {
    font-weight: 600;
    color: var(--ha-ink);
}

/* Amenities Row */
.ha-amenities-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.ha-amenity-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    background: var(--ha-surface-2);
    border-radius: var(--ha-r-sm);
    font-size: 0.75rem;
    color: var(--ha-ink-2);
    font-weight: 500;
}

/* Description */
.ha-point-desc {
    font-size: 0.8125rem;
    color: var(--ha-ink-3);
    line-height: 1.45;
    margin: 0;
}

/* Card Actions */
.ha-card-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    margin-top: 2px;
    border-top: 1px solid var(--ha-border);
}

.ha-btn-directions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 36px;
    padding: 0 14px;
    background: var(--ha-info);
    color: #fff;
    border-radius: var(--ha-r-md);
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    transition: background-color .15s ease;
}

.ha-btn-directions:hover {
    background: #1A4971;
    color: #fff;
}

.ha-btn-details {
    background: none;
    border: none;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--ha-info);
    cursor: pointer;
    padding: 6px 0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: color .15s ease;
}

.ha-btn-details:hover {
    color: #1A4971;
    text-decoration: underline;
}

/* Sticky Map Container */
.ha-cooling-map-wrap {
    position: sticky;
    top: 20px;
    height: calc(100vh - 120px);
    min-height: 480px;
    max-height: 720px;
    border-radius: var(--ha-r-lg);
    overflow: hidden;
    border: 1px solid var(--ha-border);
    box-shadow: var(--ha-shadow-rest);
    background: var(--ha-surface-2);
}

#cooling-map {
    width: 100%;
    height: 100%;
}

/* Mobile Toggle Button */
.ha-mobile-map-toggle {
    display: none;
}

@media (max-width: 1023px) {
    .ha-cooling-layout {
        grid-template-columns: 1fr;
    }
    .ha-cooling-map-wrap {
        display: none;
        height: 60vh;
        min-height: 360px;
    }
    .ha-cooling-map-wrap.is-visible-mobile {
        display: block;
    }
    .ha-cooling-list.is-hidden-mobile {
        display: none;
    }
    .ha-mobile-map-toggle {
        display: flex;
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        background: var(--ha-ink);
        color: #fff;
        border: none;
        border-radius: 999px;
        padding: 12px 22px;
        font-size: 0.875rem;
        font-weight: 600;
        box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        cursor: pointer;
        align-items: center;
        gap: 8px;
    }
    .ha-selects-wrap {
        margin-left: 0;
        width: 100%;
        justify-content: space-between;
    }
}

/* Modal for "View details" */
.ha-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(28, 26, 23, 0.45);
    backdrop-filter: blur(2px);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.ha-modal-backdrop.is-open {
    display: flex;
}
.ha-modal-card {
    background: var(--ha-surface);
    border: 1px solid var(--ha-border);
    border-radius: var(--ha-r-lg);
    box-shadow: var(--ha-shadow-pop);
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.ha-modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.ha-modal-close {
    background: var(--ha-surface-2);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--ha-ink-2);
}
.ha-modal-close:hover {
    background: var(--ha-border);
    color: var(--ha-ink);
}
</style>

<div class="ha-cp-page">
    {{-- Page Header --}}
    <header class="ha-cp-header">
        <h1 class="ha-cp-title">Cooling points</h1>
        <p class="ha-cp-subtitle">Find nearby places for shade, water, and relief from the heat.</p>
    </header>

    {{-- Search & Quick-Filters System --}}
    <form method="GET" action="{{ route('cooling-points') }}" id="cooling-search-form" class="ha-search-box" role="search">
        {{-- Search Row with "Near me" --}}
        <div class="ha-search-top-row">
            <div class="ha-search-input-wrap">
                <span class="ha-search-icon">
                    <x-ha.icon name="search" size="sm" />
                </span>
                <input class="ha-search-input" id="q" name="q" type="search" value="{{ $search }}" placeholder="Search location or cooling point..." autocomplete="off">
            </div>

            <button type="button" id="btn-near-me" class="ha-loc-btn" title="Sort locations by proximity to your current position">
                <x-ha.icon name="map-pin" size="sm" />
                <span>Near me</span>
            </button>
        </div>

        {{-- Quick Filter Chips + Point Type + Sort --}}
        <div class="ha-filter-row">
            {{-- Quick Filter Chips --}}
            <div class="ha-chips-wrap">
                @php
                    $chips = [
                        'all' => 'All',
                        'open' => 'Open now',
                        'water' => '💧 Water',
                        'shade' => '🌳 Shade',
                        'indoor' => '❄️ Indoor',
                    ];
                @endphp

                @foreach($chips as $key => $label)
                    <button type="submit" name="quick" value="{{ $key }}" class="ha-chip {{ $quick === $key ? 'is-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Dropdown Filters --}}
            <div class="ha-selects-wrap">
                <select id="type_id" name="type_id" class="ha-compact-select" onchange="this.form.submit()" aria-label="Point type">
                    <option value="">All point types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" @selected((string)$typeId === (string)$t->id)>{{ $t->nom }}</option>
                    @endforeach
                </select>

                <select id="sort" name="sort" class="ha-compact-select" onchange="this.form.submit()" aria-label="Sort cooling points">
                    <option value="nearest" @selected($sort === 'nearest')>Sort: Nearest</option>
                    <option value="name" @selected($sort === 'name')>Sort: Name (A-Z)</option>
                </select>

                @if($search !== '' || $typeId !== '' || $quick !== 'all' || $quartierId !== '')
                    <a href="{{ route('cooling-points') }}" class="ha-btn ha-btn--ghost ha-btn--sm" title="Clear all filters">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- Result Count Header --}}
    <div class="ha-results-bar">
        <span class="ha-results-count" id="count-label">
            {{ $points->count() }} cooling {{ \Illuminate\Support\Str::plural('point', $points->count()) }} nearby
        </span>
        <span id="loc-status-text" class="text-xs text-gray-500"></span>
    </div>

    {{-- Desktop Two-Column Layout: List (Left) + Interactive Map (Right) --}}
    <div class="ha-cooling-layout">
        {{-- Left: Cooling Points List --}}
        <div class="ha-cooling-list" id="cooling-points-list">
            @forelse($points as $point)
                @php
                    $status = $point->getOpenStatus();
                    $amenities = $point->getAmenities();
                @endphp

                <article class="ha-point-card" id="card-{{ $point->id }}" data-id="{{ $point->id }}" data-lat="{{ $point->latitude }}" data-lng="{{ $point->longitude }}" data-name="{{ $point->nom }}">
                    {{-- 1. Name & 2. Availability Status --}}
                    <div class="ha-card-head-row">
                        <div>
                            <h2 class="ha-point-name">{{ $point->nom }}</h2>
                            <div class="ha-card-sub-row mt-1">
                                {{-- 3. Point Type Badge --}}
                                <span class="ha-badge ha-badge--cool">{{ $point->typePoint?->nom ?? 'Cooling zone' }}</span>
                            </div>
                        </div>

                        <div class="ha-status-block">
                            @if($status['is_open'])
                                <span class="ha-status-pill ha-status-pill--open">● {{ $status['label'] }}</span>
                            @else
                                <span class="ha-status-pill ha-status-pill--closed">● {{ $status['label'] }}</span>
                            @endif
                            <span class="ha-status-sub">{{ $status['detail'] }}</span>
                        </div>
                    </div>

                    {{-- 4. Location and Distance --}}
                    <p class="ha-location-line">
                        <span>📍</span>
                        <span>{{ $point->quartier?->nom ?? $point->adresse }}</span>
                        @if($point->quartier?->ville)
                            <span class="text-gray-400">, {{ $point->quartier->ville }}</span>
                        @endif
                        <span>·</span>
                        <span class="ha-dist-badge" id="dist-{{ $point->id }}" data-point-lat="{{ $point->latitude }}" data-point-lng="{{ $point->longitude }}">Calculating...</span>
                    </p>

                    {{-- 5. Key Amenities --}}
                    @if(count($amenities) > 0)
                        <div class="ha-amenities-row">
                            @foreach($amenities as $am)
                                <span class="ha-amenity-item">{{ $am['icon'] }} {{ $am['label'] }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- 6. Short Description --}}
                    @if($point->description)
                        <p class="ha-point-desc">{{ \Illuminate\Support\Str::limit($point->description, 110) }}</p>
                    @endif

                    {{-- 7. Primary Action & 8. Secondary Action --}}
                    <div class="ha-card-actions">
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $point->latitude }},{{ $point->longitude }}" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="ha-btn-directions">
                            <x-ha.icon name="map-pin" size="sm" />
                            <span>Get directions</span>
                        </a>

                        <button type="button" class="ha-btn-details" onclick="openDetailsModal({{ $point->id }})">
                            <span>View details →</span>
                        </button>
                    </div>
                </article>
            @empty
                <div class="ha-card" style="padding: 40px; text-align: center;">
                    <x-ha.empty-state 
                        icon="snowflake" 
                        title="No cooling points match your filters" 
                        description="Try selecting 'All' or clearing your search term to see all available heat refuges.">
                        <a href="{{ route('cooling-points') }}" class="ha-btn ha-btn--primary ha-btn--sm" style="margin-top: 12px;">Reset filters</a>
                    </x-ha.empty-state>
                </div>
            @endforelse
        </div>

        {{-- Right: Interactive Map --}}
        <div class="ha-cooling-map-wrap" id="cooling-map-wrap">
            <div id="cooling-map" aria-label="Interactive map of cooling points"></div>
        </div>
    </div>
</div>

{{-- Mobile Floating Toggle Button --}}
<button type="button" id="mobile-toggle-btn" class="ha-mobile-map-toggle">
    <span id="mobile-toggle-icon">🗺️</span>
    <span id="mobile-toggle-label">View Map</span>
</button>

{{-- Details Modal --}}
<div id="details-modal" class="ha-modal-backdrop" onclick="closeDetailsModal(event)">
    <div class="ha-modal-card" onclick="event.stopPropagation()">
        <div class="ha-modal-head">
            <div>
                <h3 id="modal-title" class="ha-point-name" style="font-size: 1.25rem;">Location Name</h3>
                <span id="modal-badge" class="ha-badge ha-badge--cool" style="margin-top: 4px;">Point Type</span>
            </div>
            <button type="button" class="ha-modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>

        <div id="modal-status-box" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--ha-surface-2); border-radius: var(--ha-r-md);">
            <span id="modal-status-pill" class="ha-status-pill ha-status-pill--open">● OPEN</span>
            <span id="modal-status-sub" class="ha-status-sub">Closes 20:00</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.875rem;">
            <p style="margin: 0;"><strong>Address:</strong> <span id="modal-address">123 Street</span></p>
            <p style="margin: 0;"><strong>Hours:</strong> <span id="modal-hours">08:00 - 20:00</span></p>
            <p style="margin: 0;"><strong>Accessibility:</strong> <span id="modal-pmr">PRM Accessible</span></p>
        </div>

        <div>
            <h4 style="font-size: 0.8125rem; text-transform: uppercase; color: var(--ha-ink-3); margin: 0 0 4px 0; letter-spacing: 0.05em;">Description</h4>
            <p id="modal-desc" style="font-size: 0.875rem; color: var(--ha-ink-2); margin: 0; line-height: 1.5;"></p>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 14px; border-top: 1px solid var(--ha-border);">
            <a id="modal-dir-btn" href="#" target="_blank" rel="noopener noreferrer" class="ha-btn-directions">
                <x-ha.icon name="map-pin" size="sm" />
                <span>Get directions</span>
            </a>
            <button type="button" class="ha-btn ha-btn--ghost ha-btn--sm" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

{{-- JSON Data payload for Map & Details Modal --}}
<script>
window.coolingPointsData = @json($mapPoints);
</script>

{{-- Client-side Map, Distance Calculation & Modal Logic --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Default user coordinate fallback (Tunis / Ben Arous region)
    var userPos = { lat: 36.8065, lng: 10.1815 };
    var hasRealLocation = false;

    // Haversine formula to compute distance in km
    function calculateDistance(lat1, lon1, lat2, lon2) {
        var R = 6371; // Earth radius in km
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLon = (lon2 - lon1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    // Reorder cards in the DOM by distance (nearest first)
    function sortCardsByDistance() {
        var list = document.getElementById('cooling-points-list');
        if (!list) return;
        var cards = Array.from(list.querySelectorAll('.ha-point-card'));
        cards.sort(function (a, b) {
            var dA = parseFloat(a.getAttribute('data-distance')) || 999999;
            var dB = parseFloat(b.getAttribute('data-distance')) || 999999;
            return dA - dB;
        });
        cards.forEach(function (card) {
            list.appendChild(card);
        });
    }

    // Update distance badges on cards
    function updateDistances(shouldSort) {
        document.querySelectorAll('.ha-point-card').forEach(function (card) {
            var pLat = parseFloat(card.getAttribute('data-lat'));
            var pLng = parseFloat(card.getAttribute('data-lng'));
            var badge = card.querySelector('.ha-dist-badge');
            if (!isNaN(pLat) && !isNaN(pLng)) {
                var d = calculateDistance(userPos.lat, userPos.lng, pLat, pLng);
                card.setAttribute('data-distance', d);
                if (badge) {
                    badge.textContent = d.toFixed(1) + ' km away';
                }
            } else {
                if (badge) badge.textContent = 'Distance unavailable';
            }
        });

        if (shouldSort) {
            sortCardsByDistance();
        }
    }

    // Initial distance calculation on load
    updateDistances(false);

    // Leaflet Interactive Map Initialization
    var mapContainer = document.getElementById('cooling-map');
    var map = null;
    var markersMap = {};
    var userMarker = null;

    if (mapContainer && typeof L !== 'undefined' && window.coolingPointsData.length > 0) {
        // Initialize map centered on first point or user position
        var first = window.coolingPointsData[0];
        map = L.map('cooling-map', {
            zoomControl: true,
            scrollWheelZoom: false
        }).setView([first.lat, first.lng], 13);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var bounds = [];

        var typeStyles = {
            'tree': { emoji: '🌳', color: '#2F855A' },
            'droplet': { emoji: '💧', color: '#0284C7' },
            'waves': { emoji: '🏊', color: '#2563EB' },
            'snowflake': { emoji: '❄️', color: '#0F766E' },
            'book': { emoji: '📚', color: '#6D28D9' },
            'sun': { emoji: '☀️', color: '#D97706' },
            'building': { emoji: '🏛️', color: '#4B5563' }
        };

        window.coolingPointsData.forEach(function (point) {
            if (point.lat && point.lng) {
                bounds.push([point.lat, point.lng]);

                var style = typeStyles[point.icon] || { emoji: '💧', color: '#0284C7' };
                var pinBg = point.is_open ? style.color : '#718096';

                // Adaptive point-type marker pin
                var markerHtml = '<div style="background:' + pinBg + '; width:28px; height:28px; border-radius:50%; border:2px solid #ffffff; box-shadow:0 2px 7px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:13px; cursor:pointer;" title="' + point.name + ' (' + point.type + ')">' + style.emoji + '</div>';
                var customIcon = L.divIcon({
                    className: 'ha-custom-pin',
                    html: markerHtml,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14]
                });

                var marker = L.marker([point.lat, point.lng], { icon: customIcon }).addTo(map);
                marker.bindPopup(
                    '<div style="font-family:sans-serif; min-width:160px;">' +
                        '<strong style="font-size:14px; color:#1C1A17; display:block; margin-bottom:2px;">' + point.name + '</strong>' +
                        '<span style="font-size:11px; color:#5E574E;">' + point.type + '</span><br>' +
                        '<span style="font-size:11px; font-weight:bold; color:' + (point.is_open ? '#2F855A' : '#C53030') + ';">● ' + point.status_label + ' (' + point.status_detail + ')</span>' +
                        '<div style="margin-top:8px;"><a href="https://www.google.com/maps/dir/?api=1&destination=' + point.lat + ',' + point.lng + '" target="_blank" style="display:inline-block; padding:4px 8px; background:#2B6CB0; color:#fff; border-radius:4px; font-size:11px; text-decoration:none;">Directions &rarr;</a></div>' +
                    '</div>'
                );

                marker.on('click', function () {
                    highlightCard(point.id);
                });

                markersMap[point.id] = marker;
            }
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    // Highlight card when marker is clicked
    function highlightCard(id) {
        document.querySelectorAll('.ha-point-card').forEach(function (card) {
            card.classList.remove('is-selected');
        });
        var targetCard = document.getElementById('card-' + id);
        if (targetCard) {
            targetCard.classList.add('is-selected');
            targetCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Card hover highlights map marker
    document.querySelectorAll('.ha-point-card').forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            var id = this.getAttribute('data-id');
            if (markersMap[id] && map) {
                markersMap[id].openPopup();
            }
        });
    });

    // "Near me" button geolocation with smooth card reordering and map centering
    var btnNearMe = document.getElementById('btn-near-me');
    if (btnNearMe) {
        btnNearMe.addEventListener('click', function (e) {
            e.preventDefault();

            btnNearMe.innerHTML = '<span>📍 Locating...</span>';

            if (!navigator.geolocation) {
                fallbackToCentralLocation('Geolocation is not supported by your browser');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    userPos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    hasRealLocation = true;
                    btnNearMe.classList.add('is-active');
                    btnNearMe.innerHTML = '<span>📍 Near me (Active)</span>';

                    var statusText = document.getElementById('loc-status-text');
                    if (statusText) statusText.textContent = '✓ Sorted by proximity to your GPS position';

                    updateDistances(true);

                    if (map) {
                        if (userMarker) {
                            map.removeLayer(userMarker);
                        }
                        userMarker = L.circleMarker([userPos.lat, userPos.lng], {
                            radius: 9,
                            fillColor: '#3182CE',
                            color: '#ffffff',
                            weight: 3,
                            fillOpacity: 0.95
                        }).addTo(map).bindPopup('<strong>📍 Your Location</strong>').openPopup();

                        map.flyTo([userPos.lat, userPos.lng], 13, { duration: 1.2 });
                    }
                },
                function (err) {
                    fallbackToCentralLocation('Location access not granted. Sorted by city center proximity.');
                },
                { enableHighAccuracy: true, timeout: 6000 }
            );

            function fallbackToCentralLocation(msg) {
                userPos = { lat: 36.8065, lng: 10.1815 }; // Central Tunis / Ben Arous hub
                btnNearMe.classList.add('is-active');
                btnNearMe.innerHTML = '<span>📍 Sorted by distance</span>';

                var statusText = document.getElementById('loc-status-text');
                if (statusText) statusText.textContent = '✓ ' + msg;

                updateDistances(true);

                if (map) {
                    map.flyTo([userPos.lat, userPos.lng], 13, { duration: 1.0 });
                }
            }
        });
    }

    // Mobile Map Toggle
    var mobileToggleBtn = document.getElementById('mobile-toggle-btn');
    var mapWrap = document.getElementById('cooling-map-wrap');
    var listWrap = document.getElementById('cooling-points-list');
    var isMapActiveOnMobile = false;

    if (mobileToggleBtn && mapWrap && listWrap) {
        mobileToggleBtn.addEventListener('click', function () {
            isMapActiveOnMobile = !isMapActiveOnMobile;
            if (isMapActiveOnMobile) {
                mapWrap.classList.add('is-visible-mobile');
                listWrap.classList.add('is-hidden-mobile');
                document.getElementById('mobile-toggle-icon').textContent = '📋';
                document.getElementById('mobile-toggle-label').textContent = 'View List';
                if (map) {
                    setTimeout(function() { map.invalidateSize(); }, 200);
                }
            } else {
                mapWrap.classList.remove('is-visible-mobile');
                listWrap.classList.remove('is-hidden-mobile');
                document.getElementById('mobile-toggle-icon').textContent = '🗺️';
                document.getElementById('mobile-toggle-label').textContent = 'View Map';
            }
        });
    }
});

// Modal Logic
window.openDetailsModal = function (pointId) {
    var point = window.coolingPointsData.find(function (p) { return p.id === pointId; });
    if (!point) return;

    document.getElementById('modal-title').textContent = point.name;
    document.getElementById('modal-badge').textContent = point.type;
    document.getElementById('modal-address').textContent = point.address + (point.neighborhood ? ' (' + point.neighborhood + ')' : '');
    document.getElementById('modal-hours').textContent = point.hours;
    document.getElementById('modal-desc').textContent = point.description;
    document.getElementById('modal-pmr').textContent = point.pmr ? 'Yes, PRM Accessible' : 'Not specifically adapted for PRM';

    var statusPill = document.getElementById('modal-status-pill');
    var statusSub = document.getElementById('modal-status-sub');
    if (point.is_open) {
        statusPill.className = 'ha-status-pill ha-status-pill--open';
        statusPill.textContent = '● ' + point.status_label;
    } else {
        statusPill.className = 'ha-status-pill ha-status-pill--closed';
        statusPill.textContent = '● ' + point.status_label;
    }
    statusSub.textContent = point.status_detail;

    document.getElementById('modal-dir-btn').href = 'https://www.google.com/maps/dir/?api=1&destination=' + point.lat + ',' + point.lng;

    document.getElementById('details-modal').classList.add('is-open');
};

window.closeDetailsModal = function (event) {
    document.getElementById('details-modal').classList.remove('is-open');
};
</script>
@endsection
