<?php

namespace App\Providers;

use App\Models\Admin;
use App\Support\MonitoringAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Configure Horizon authorization — always require MonitoringAccess
     * (no open access in the local environment).
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon');
        });
    }

    /**
     * Register the Horizon gate (same root/super-admin + permission check as Pulse / Telescope / Log Viewer).
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null): bool {
            $admin = Auth::guard('admin')->user();

            return $admin instanceof Admin && MonitoringAccess::allows($admin);
        });
    }
}
