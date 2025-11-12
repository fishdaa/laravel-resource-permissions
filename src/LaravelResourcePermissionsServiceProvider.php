<?php

namespace Fishdaa\LaravelResourcePermissions;

use Illuminate\Support\ServiceProvider;

class LaravelResourcePermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/resource-permissions.php',
            'resource-permissions'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/resource-permissions.php' => config_path('resource-permissions.php'),
        ], 'resource-permissions-config');

        $this->publishes([
            __DIR__ . '/database/migrations' => database_path('migrations'),
        ], 'resource-permissions-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}

