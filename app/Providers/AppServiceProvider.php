<?php

namespace App\Providers;

use App\Models\Setting;
use App\NotificationChannel\EventChannel;
use App\NotificationChannel\FirebaseChannel;
use App\Services\Chat\ChatService;
use App\Services\Chat\Contracts\IChatService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Notification;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/arrays.php');

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(IChatService::class, ChatService::class);
        $this->app->singleton('settings', fn () => cache()->rememberForever('settings', fn () => Setting::pluck('content', 'key')));
        JsonResource::withoutWrapping();
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
        Authenticate::redirectUsing(static function (Request $request) {
            if ($request->routeIs('dashboard.*')) {
                return route('dashboard.login.form');
            }

            if ($request->routeIs('provider.*')) {
                return route('provider.login');
            }

            return route('login');
        });
        RedirectIfAuthenticated::redirectUsing(static function (Request $request) {
            if ($request->routeIs('dashboard.*')) {
                return route('dashboard.home');
            }

            if ($request->routeIs('provider.*')) {
                return route('provider.home');
            }

            return route('/');
        });
        Notification::extend('firebase', static fn ($app) => $app->make(FirebaseChannel::class));
        Notification::extend('event', static fn ($app) => $app->make(EventChannel::class));
    }
}
