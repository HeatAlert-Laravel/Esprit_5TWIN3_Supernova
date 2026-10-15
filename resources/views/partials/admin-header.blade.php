<header class="ha-topbar">
    <div class="ha-topbar__left">
        <button type="button" @click="$store.sidebar.toggleMobileOpen()" class="ha-menu-btn" aria-label="Toggle navigation" aria-controls="sidebar">
            <x-ha.icon name="menu" />
        </button>
        <p class="ha-topbar__title">@yield('title', 'Dashboard')</p>
    </div>
    <div class="ha-topbar__right">
        <div class="ha-topbar__user">
            <x-ha.avatar :name="auth()->user()->name" />
            <div class="ha-topbar__who">
                <strong>{{ auth()->user()->name }}</strong>
                <span>{{ ucfirst(strtolower(auth()->user()->role)) }}</span>
            </div>
        </div>
        <form novalidate method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="ha-btn ha-btn--outline ha-btn--sm"><x-ha.icon name="log-out" size="sm" /><span class="ha-btn-label-sm">Log out</span></button>
        </form>
    </div>
</header>
