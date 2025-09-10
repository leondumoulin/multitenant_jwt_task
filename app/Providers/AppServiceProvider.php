<?php

namespace App\Providers;

use App\Http\Guards\AdminJwtGuard;
use App\Http\Guards\TenantJwtGuard;
use Illuminate\Support\Facades\Auth;
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
        // Register custom JWT guards
        Auth::extend('admin-jwt', function ($app, $name, array $config) {
            return new AdminJwtGuard(
                $app->make(\App\Services\JwtService::class),
                Auth::createUserProvider($config['provider']),
                $app->make('request')
            );
        });

        Auth::extend('tenant-jwt', function ($app, $name, array $config) {
            return new TenantJwtGuard(
                $app->make(\App\Services\JwtService::class),
                $app->make(\App\Services\DatabaseManager::class),
                Auth::createUserProvider($config['provider']),
                $app->make('request')
            );
        });
    }
}
