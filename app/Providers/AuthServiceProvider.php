<?php

namespace App\Providers;

use App\Models\GideonSparringSession;
use App\Policies\GideonSparringSessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        GideonSparringSession::class => GideonSparringSessionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // If you later add Gates, define them here.
        // Policies registered in $policies are auto-discovered by Laravel.
    }
}
