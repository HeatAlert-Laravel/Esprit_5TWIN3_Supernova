@extends('layouts.app')
@section('title', $point->nom)
@section('content')
<x-ha.page-header :title="$point->nom" :description="$point->adresse" :breadcrumbs="[['Dashboard', route('admin.dashboard')], ['Cooling points', route('admin.point-fraicheurs.index')], [$point->nom, null]]">
    <x-slot:badges>
        <span class="ha-badge {{ $point->actif ? 'ha-badge--success' : 'ha-badge--danger' }}">{{ $point->actif ? 'Active (visible)' : 'Inactive (hidden)' }}</span>
        @if($point->accessible_pmr)
            <span class="ha-badge ha-badge--info">PRM Accessible</span>
        @endif
    </x-slot:badges>
    <x-slot:actions>
        <a href="{{ route('admin.point-fraicheurs.edit', $point) }}" class="ha-btn ha-btn--primary"><x-ha.icon name="pencil" size="sm" />Edit</a>
        <form method="POST" action="{{ route('admin.point-fraicheurs.destroy', $point) }}" onsubmit="return confirm('Delete this cooling point?')">
            @csrf @method('DELETE')
            <button class="ha-btn ha-btn--danger-soft"><x-ha.icon name="trash" size="sm" />Delete</button>
        </form>
    </x-slot:actions>
</x-ha.page-header>

<div class="ha-grid ha-grid--main-rev">
    <section class="ha-card" aria-labelledby="point-info">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="point-info">
                <span class="ha-icon-chip"><x-cooling-icon :name="$point->typePoint?->icone ?? 'snowflake'" /></span>
                Location details
            </h2>
        </div>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Name</dt><dd class="font-medium">{{ $point->nom }}</dd></div>
            <div>
                <dt>Point type</dt>
                <dd>
                    @if($point->typePoint)
                        <a href="{{ route('admin.type-points.show', $point->typePoint) }}" class="ha-badge ha-badge--neutral hover:underline">
                            <x-cooling-icon :name="$point->typePoint->icone ?? 'sun'" size="sm" />
                            {{ $point->typePoint->nom }}
                        </a>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </dd>
            </div>
            <div><dt>Neighborhood (Member 1)</dt><dd class="font-medium">{{ $point->quartier?->nom ?? '—' }} ({{ $point->quartier?->ville }})</dd></div>
            <div><dt>Address</dt><dd>{{ $point->adresse }}</dd></div>
            <div><dt>Hours</dt><dd class="ha-mono">{{ $point->horaires ?? 'Not specified' }}</dd></div>
            <div>
                <dt>GPS Coordinates</dt>
                <dd class="ha-mono">
                    @if($point->latitude && $point->longitude)
                        {{ $point->latitude }}, {{ $point->longitude }}
                    @else
                        <span class="text-gray-400">Not recorded</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>PRM Accessible</dt>
                <dd>
                    @if($point->accessible_pmr)
                        <span class="ha-badge ha-badge--success">Yes</span>
                    @else
                        <span class="ha-badge ha-badge--neutral">No</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Publication status</dt>
                <dd>
                    <form method="POST" action="{{ route('admin.point-fraicheurs.toggle-status', $point) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="ha-btn ha-btn--sm {{ $point->actif ? 'ha-btn--danger-soft' : 'ha-btn--primary' }}">
                            {{ $point->actif ? 'Deactivate (hide)' : 'Activate (make visible)' }}
                        </button>
                    </form>
                </dd>
            </div>
            @if($point->description)
                <div class="sm:col-span-2"><dt>Description & Guidelines</dt><dd class="text-gray-600 dark:text-gray-300">{{ $point->description }}</dd></div>
            @endif
        </dl>

        <x-ha.divider>Record metadata</x-ha.divider>
        <dl class="ha-dl ha-dl--2">
            <div><dt>Created at</dt><dd class="ha-mono">{{ $point->created_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
            <div><dt>Last updated</dt><dd class="ha-mono">{{ $point->updated_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="ha-card" aria-labelledby="point-actions">
        <div class="ha-card__head">
            <h2 class="ha-card__title ha-card__title--with-icon" id="point-actions">
                <span class="ha-icon-chip"><x-ha.icon name="lightbulb" /></span>
                Role in HeatAlert
            </h2>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
            This cooling spot provides residents in <strong>{{ $point->quartier?->nom }}</strong> with quick relief during heatwave events.
        </p>
        @if($point->latitude && $point->longitude)
            <div style="margin-top: 16px;">
                <div id="admin-map" style="height: 260px; width: 100%; border-radius: var(--ha-r-md); border: 1px solid var(--ha-border); overflow: hidden;"></div>
            </div>

            <a href="https://www.google.com/maps/search/?api=1&query={{ $point->latitude }},{{ $point->longitude }}" target="_blank" rel="noopener noreferrer" class="ha-btn ha-btn--outline w-full justify-center" style="margin-top: 12px;">
                <x-ha.icon name="external-link" size="sm" />
                View on Google Maps
            </a>

            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            @php
                $typeIconMap = [
                    'tree' => ['emoji' => '🌳', 'color' => '#2F855A'],
                    'droplet' => ['emoji' => '💧', 'color' => '#0284C7'],
                    'waves' => ['emoji' => '🏊', 'color' => '#2563EB'],
                    'snowflake' => ['emoji' => '❄️', 'color' => '#0F766E'],
                    'book' => ['emoji' => '📚', 'color' => '#6D28D9'],
                    'sun' => ['emoji' => '☀️', 'color' => '#D97706'],
                    'building' => ['emoji' => '🏛️', 'color' => '#4B5563'],
                ];
                $iconKey = $point->typePoint?->icone ?? 'snowflake';
                $pinMeta = $typeIconMap[$iconKey] ?? ['emoji' => '💧', 'color' => '#0284C7'];
            @endphp
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                var lat = {{ (float)$point->latitude }};
                var lng = {{ (float)$point->longitude }};
                if (lat && lng && typeof L !== 'undefined') {
                    var map = L.map('admin-map', {
                        zoomControl: true,
                        scrollWheelZoom: false
                    }).setView([lat, lng], 14);

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

                    var markerHtml = '<div style="background:{{ $pinMeta['color'] }}; width:28px; height:28px; border-radius:50%; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,0.3); display:flex; align-items:center; justify-content:center; color:#fff; font-size:13px;" title="{{ addslashes($point->typePoint?->nom ?? '') }}">{{ $pinMeta['emoji'] }}</div>';
                    var customIcon = L.divIcon({
                        className: 'ha-admin-pin',
                        html: markerHtml,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    var marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);
                    marker.bindPopup('<strong>{{ addslashes($point->nom) }}</strong><br><span style="font-size:12px; color:#555;">{{ addslashes($point->adresse) }}</span>').openPopup();
                }
            });
            </script>
        @endif
    </section>
</div>
@endsection
