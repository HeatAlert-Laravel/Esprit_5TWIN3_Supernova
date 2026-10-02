<?php

namespace App\Providers;

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
        $this->loadMigrationsFrom(base_path('../shared/database/migrations'));

        // Display-only readiness checklist (see App\Support\Readiness) for the home page and My Profile.
        View::composer('partials.readiness-card', fn ($view) => $view->with('readiness', Readiness::for(auth()->user())));

        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
