<?php

namespace App\Providers;

use App\Models\Admin;
use App\UserProviders\EloquentAdminProvider;
use Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Auth::Provider('adminEloquent', function (Application $app, array $config) {
            return new EloquentAdminProvider($app['hash'], $config['model']);
        });

        Gate::before(function (Authenticatable $user) {
            if ($user instanceof Admin && $user->root) {
                return true;
            }

            return null;
        });
    }
}
