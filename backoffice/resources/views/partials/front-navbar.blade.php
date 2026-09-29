<header class="border-b border-orange-200 bg-white">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-orange-800"><img src="{{ Vite::asset('resources/images/heat-alert-mark.svg') }}" alt="" class="h-9 w-9">HeatAlert</a>
        <nav class="flex flex-wrap items-center gap-3 text-sm font-medium text-gray-700" aria-label="Main navigation">
            @foreach ([['home','Home'],['weather-alerts','Weather Alerts'],['outages','Outages'],['cooling-points','Cooling Points'],['advice','Advice']] as [$name,$label])
                <a href="{{ route($name) }}" class="rounded-lg px-2 py-2 hover:bg-orange-100 {{ request()->routeIs($name) ? 'text-orange-700' : '' }}">{{ $label }}</a>
            @endforeach
            @auth
                <a href="{{ route('my-profile') }}" class="rounded-lg px-2 py-2 hover:bg-orange-100">My Profile</a>
                @if(auth()->user()->role === 'ADMIN')<a href="{{ route('admin.dashboard') }}" class="rounded-lg px-2 py-2 hover:bg-orange-100">Admin</a>@endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-orange-300 px-3 py-2 hover:bg-orange-100">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg px-2 py-2 hover:bg-orange-100">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-orange-600 px-3 py-2 text-white hover:bg-orange-700">Register</a>
            @endauth
        </nav>
    </div>
</header>
