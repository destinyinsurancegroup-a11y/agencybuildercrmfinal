<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

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
        // Fix MySQL string length for older database versions
        Schema::defaultStringLength(191);

        // Force timezone for the entire application (fixes calendar times)
        date_default_timezone_set('America/New_York');

        // Set Laravel's internal timezone
        config(['app.timezone' => 'America/New_York']);

        // 🔐 Force HTTPS when deployed on DigitalOcean (production)
        if (env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
    }
}
