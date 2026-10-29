<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reads the Open-Meteo forecast of every neighborhood and publishes the matching weather alerts.
// Run `php artisan schedule:work` locally, or a cron entry calling `schedule:run` every minute in production.
Schedule::command('alerts:generate')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/alerts-generate.log'));
