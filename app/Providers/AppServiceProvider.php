<?php

namespace App\Providers;

use App\Contracts\Repositories\CarAdvisement\CarAdvisementRepositoryInterface;
use App\Contracts\Repositories\CarBrandRepositoryInterface;
use App\Contracts\Repositories\CarCategoryRepositoryInterface;
use App\Contracts\Repositories\CarTypeRepositoryInterface;
use App\Contracts\Repositories\PropertyAdvisement\PropertyAdvisementRepositoryInterface;
use App\Contracts\Repositories\PropertyCategory\PropertyCategoryRepositoryInterface;
use App\Contracts\Repositories\PropertyType\PropertyTypeRepositoryInterface;
use App\Contracts\Services\CarBrandServiceInterface;
use App\Contracts\Services\CarCategoryServiceInterface;
use App\Contracts\Services\CarTypeServiceInterface;
use App\Models\Setting;
use App\NotificationChannel\EventChannel;
use App\NotificationChannel\FirebaseChannel;
use App\Repositories\CarAdvisement\CarAdvisementRepository;
use App\Repositories\CarBrandRepository;
use App\Repositories\CarCategoryRepository;
use App\Repositories\CarTypeRepository;
use App\Repositories\PropertyAdvisement\PropertyAdvisementRepository;
use App\Repositories\PropertyCategory\PropertyCategoryRepository;
use App\Repositories\PropertyType\PropertyTypeRepository;
use App\Services\CarBrandService;
use App\Services\CarCategoryService;
use App\Services\CarTypeService;
use App\Services\Chat\ChatService;
use App\Services\Chat\Contracts\IChatService;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/arrays.php');

        $this->app->bind(
            CarAdvisementRepositoryInterface::class,
            CarAdvisementRepository::class,
        );

        $this->app->bind(
            PropertyAdvisementRepositoryInterface::class,
            PropertyAdvisementRepository::class,
        );

        $this->app->bind(
            PropertyTypeRepositoryInterface::class,
            PropertyTypeRepository::class,
        );

        $this->app->bind(
            PropertyCategoryRepositoryInterface::class,
            PropertyCategoryRepository::class,
        );

        $this->app->bind(
            CarBrandRepositoryInterface::class,
            CarBrandRepository::class,
        );

        $this->app->bind(
            CarTypeRepositoryInterface::class,
            CarTypeRepository::class,
        );

        $this->app->bind(
            CarCategoryRepositoryInterface::class,
            CarCategoryRepository::class,
        );

        $this->app->bind(
            CarBrandServiceInterface::class,
            CarBrandService::class,
        );

        $this->app->bind(
            CarTypeServiceInterface::class,
            CarTypeService::class,
        );

        $this->app->bind(
            CarCategoryServiceInterface::class,
            CarCategoryService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->routes(static fn (Route $route): bool => str_starts_with($route->uri, 'api/v1/'))
            ->expose(ui: '/docs/api', document: '/docs/api.json')
            ->withDocumentTransformers(static function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        Gate::define('viewApiDocs', static function ($user = null): bool {
            if (app()->environment(['local', 'testing'])) {
                return true;
            }

            $admin = Auth::guard('admin')->user();

            return (bool) ($admin?->root);
        });

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
