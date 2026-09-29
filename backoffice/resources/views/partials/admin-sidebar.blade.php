<aside class="fixed top-0 start-0 z-50 h-screen w-[90px] border-e border-orange-100 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 [.sidebar-expanded_&]:w-[290px]" :class="{'max-xl:-translate-x-full max-xl:rtl:translate-x-full': !$store.sidebar.isMobileOpen}">
    <a href="{{ route('admin.dashboard') }}" class="mb-10 flex items-center gap-3 text-xl font-bold text-orange-700 dark:text-orange-300">
        <img src="{{ Vite::asset('resources/images/heat-alert-mark.svg') }}" alt="" class="h-10 w-10 shrink-0">
        <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">HeatAlert</span>
    </a>
    <nav class="space-y-2" aria-label="Admin navigation">
        @foreach ([['admin.dashboard', 'Dashboard', '▦'], ['admin.profiles.index', 'Profiles', '♙'], ['admin.equipment.index', 'Sensitive equipment', '◈']] as [$name, $label, $icon])
            <a href="{{ route($name) }}" title="{{ $label }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium {{ request()->routeIs($name) || ($name === 'admin.profiles.index' && request()->routeIs('admin.profiles.*')) || ($name === 'admin.equipment.index' && request()->routeIs('admin.equipment.*')) ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100' : 'text-gray-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                <span class="w-6 text-center text-xl" aria-hidden="true">{{ $icon }}</span>
                <span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">{{ $label }}</span>
            </a>
        @endforeach
        <a href="{{ config('app.frontoffice_url') }}" title="Public site" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-gray-600 hover:bg-orange-50 dark:text-gray-300 dark:hover:bg-gray-800"><span class="w-6 text-center text-xl" aria-hidden="true">⌂</span><span x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen">Public site</span></a>
    </nav>
    <p x-show="$store.sidebar.isExpanded || $store.sidebar.isHovered || $store.sidebar.isMobileOpen" class="mt-10 rounded-xl bg-orange-50 p-4 text-xs leading-5 text-orange-900 dark:bg-gray-800 dark:text-orange-200">Local heat resilience starts with clear information and prepared households.</p>
</aside>
