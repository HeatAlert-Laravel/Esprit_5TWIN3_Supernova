<?php

namespace App\Providers;

use App\Models\Profile;
use App\Models\SensitiveEquipment;
use App\Models\TypeEquipement;
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
        $this->loadMigrationsFrom(base_path('../shared/database/migrations'));

        // Display-only counts for the admin sidebar badges (real data, three COUNT queries).
        View::composer('partials.admin-sidebar', fn ($view) => $view->with('sidebarCounts', [
            'profiles' => Profile::count(),
            'equipment' => SensitiveEquipment::count(),
            'types' => TypeEquipement::count(),
        ]));

        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
