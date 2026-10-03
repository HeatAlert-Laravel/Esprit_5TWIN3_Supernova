@php
    // Real counts supplied by the view composer in AppServiceProvider (two cheap COUNT queries).
    $navGroups = [
        'Overview' => [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
        ],
        'Residents' => [
            ['route' => 'admin.profiles.index', 'match' => 'admin.profiles.*', 'label' => 'Profiles', 'icon' => 'users', 'count' => $sidebarCounts['profiles'] ?? null],
            ['route' => 'admin.equipment.index', 'match' => 'admin.equipment.*', 'label' => 'Sensitive equipment', 'icon' => 'plug', 'count' => $sidebarCounts['equipment'] ?? null],
            ['route' => 'admin.type-equipements.index', 'match' => 'admin.type-equipements.*', 'label' => 'Equipment types', 'icon' => 'thermometer', 'count' => $sidebarCounts['types'] ?? null],
        ],
        'Heat planning' => [
            ['route' => 'admin.quartiers.index', 'match' => 'admin.quartiers.*', 'label' => 'Neighborhoods', 'icon' => 'map-pin'],
            ['route' => 'admin.alertes-meteo.index', 'match' => 'admin.alertes-meteo.*', 'label' => 'Weather alerts', 'icon' => 'alert-triangle'],
        ],
    ];
    $plannedModules = [['Alerts', 'alert-triangle'], ['Outages', 'zap'], ['Cooling points', 'snowflake']];
@endphp
<aside id="sidebar" class="ha-sidebar" aria-label="Admin sidebar"
    x-data="{ get open() { return $store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen } }"
    :class="{'max-xl:-translate-x-full max-xl:rtl:translate-x-full': !$store.sidebar.isMobileOpen}">
    <div class="ha-sidebar__brand">
        {{-- Full lockup when the sidebar is open, mark only when it is collapsed. --}}
        <x-logo x-show="open" variant="dark" :size="32" :href="route('admin.dashboard')" label="HeatAlert administration" />
        <x-logo x-show="!open" variant="dark" :size="32" :wordmark="false" :href="route('admin.dashboard')" label="HeatAlert administration" />
    </div>

    <nav aria-label="Admin navigation">
        @foreach($navGroups as $groupLabel => $items)
            <div class="ha-sidebar__group">
                <p class="ha-sidebar__label" x-show="open">{{ $groupLabel }}</p>
                <ul class="ha-nav-list">
                    @foreach($items as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" class="ha-nav-item" @if(request()->routeIs($item['match'])) aria-current="page" @endif>
                                <x-ha.icon :name="$item['icon']" />
                                <span class="ha-nav-item__text" x-show="open">{{ $item['label'] }}</span>
                                @if(isset($item['count']))<span class="ha-nav-count" x-show="open">{{ $item['count'] }}</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="ha-sidebar__group">
            <p class="ha-sidebar__label" x-show="open">Modules</p>
            <ul class="ha-nav-list">
                @foreach($plannedModules as [$moduleLabel, $moduleIcon])
                    <li>
                        <span class="ha-nav-item ha-nav-item--soon" title="{{ $moduleLabel }} — coming soon" aria-disabled="true">
                            <x-ha.icon :name="$moduleIcon" />
                            <span class="ha-nav-item__text" x-show="open">{{ $moduleLabel }}</span>
                            <span class="ha-nav-soon" x-show="open">Soon</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>

    <div class="ha-sidebar__foot">
        <a href="{{ config('app.frontoffice_url') }}" title="Public site" class="ha-nav-item">
            <x-ha.icon name="home" />
            <span class="ha-nav-item__text" x-show="open">Public site</span>
            <x-ha.icon name="external-link" size="sm" x-show="open" />
        </a>
    </div>
</aside>
