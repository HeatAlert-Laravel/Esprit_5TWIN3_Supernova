<header class="sticky top-0 z-40 flex items-center justify-between border-b border-orange-100 bg-white px-5 py-4 dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center gap-3">
        <button type="button" @click="$store.sidebar.toggleMobileOpen()" class="rounded-lg border border-gray-200 px-3 py-2 xl:hidden" aria-label="Toggle navigation">☰</button>
        <div><p class="text-xs font-semibold uppercase tracking-widest text-orange-600">HeatAlert</p><p class="font-semibold text-gray-800 dark:text-white">Administration</p></div>
    </div>
    <div class="flex items-center gap-4 text-sm text-gray-700 dark:text-gray-200">
        <span>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-gray-300 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Log out</button></form>
    </div>
</header>
