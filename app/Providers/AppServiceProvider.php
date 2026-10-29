<?php

namespace App\Providers;

use App\Models\PointFraicheur;
use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
use App\Models\TypePoint;
use App\Support\Readiness;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Display-only counts for the admin sidebar badges (real data).
        View::composer('partials.admin-sidebar', fn ($view) => $view->with('sidebarCounts', [
            'profiles' => Profile::count(),
            'equipment' => SensitiveEquipment::count(),
            'types' => TypeEquipement::count(),
            'cooling_points' => PointFraicheur::count(),
            'point_types' => TypePoint::count(),
        ]));

        // Display-only readiness checklist (see App\Support\Readiness) for the home page and My Profile.
        View::composer('partials.readiness-card', fn ($view) => $view->with('readiness', Readiness::for(auth()->user())));

        // Match the canonical URL; the private lab runs production over HTTP.
        if (
            config('app.env') === 'production'
            && str_starts_with((string) config('app.url'), 'https://')
        ) {
            URL::forceScheme('https');
        }
    }
}
