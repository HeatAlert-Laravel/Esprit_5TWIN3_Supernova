<header class="ha-navbar">
    <div class="ha-container ha-navbar__inner">
        <x-logo variant="light" :size="36" :href="route('home')" />
        <button type="button" class="ha-nav__toggle" data-nav-toggle aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation"><x-ha.icon name="menu" /></button>
        <div class="ha-nav__panel" id="main-nav">
            <nav aria-label="Main navigation">
                <ul class="ha-nav__links">
                    @foreach ([['home','Home'],['weather-alerts','Weather Alerts'],['outages','Outages'],['cooling-points','Cooling Points'],['advice','Advice']] as [$name,$label])
                        <li><a href="{{ route($name) }}" class="ha-nav__link" @if(request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a></li>
                    @endforeach
                    @auth
                        <li><a href="{{ route('my-profile') }}" class="ha-nav__link" @if(request()->routeIs('my-profile*', 'profile.equipment.*')) aria-current="page" @endif>My Profile</a></li>
                        @if(auth()->user()->role === 'ADMIN')<li><a href="{{ rtrim(config('app.backoffice_url'), '/') }}/admin" class="ha-nav__link">Admin</a></li>@endif
                    @endauth
                </ul>
            </nav>
            <div class="ha-nav__actions">
                @auth
                    <form novalidate method="POST" action="{{ route('logout') }}">@csrf<button class="ha-btn ha-btn--outline ha-btn--sm"><x-ha.icon name="log-out" size="sm" />Log out</button></form>
                @else
                    <a href="{{ route('login') }}" class="ha-btn ha-btn--ghost">Login</a>
                    <a href="{{ route('register') }}" class="ha-btn ha-btn--primary">Create account</a>
                @endauth
            </div>
        </div>
    </div>
</header>
