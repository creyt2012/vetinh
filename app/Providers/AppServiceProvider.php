<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Billing\PaymentManager::class, function ($app) {
            return new \App\Services\Billing\PaymentManager($app);
        });
        $this->app->singleton(\App\Services\TenantManager::class, function ($app) {
            return new \App\Services\TenantManager();
        });
        $this->app->singleton(\App\Services\Intelligence\IntelligenceService::class, function ($app) {
            return new \App\Services\Intelligence\IntelligenceService();
        });
        $this->app->singleton(\App\Services\Orbital\OrbitalService::class, function ($app) {
            return new \App\Services\Orbital\OrbitalService($app->make(\Vortex\Aerospace\SatelliteEngine::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('local')) {
            error_reporting(error_reporting() & ~E_DEPRECATED);
        }
    }
}
