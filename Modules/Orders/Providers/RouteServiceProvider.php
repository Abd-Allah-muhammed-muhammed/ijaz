<?php

namespace Modules\Orders\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Orders';

    public function boot(): void
    {
        $this->map();
        $this->mapProviderRoutes();
    }

    /**
     * Override: load Routes/V1/api.php WITHOUT the default api.v1.orders.
     * name prefix — User API order routes were previously unnamed and must stay that way.
     */
    protected function mapApiRoutes(string $version, string $prefix, string $namePrefix): void
    {
        $routesPath = module_path($this->moduleName, 'Routes/'.$version.'/api.php');

        if (! is_file($routesPath)) {
            return;
        }

        Route::middleware('api')
            ->prefix($prefix)
            ->group($routesPath);
    }

    protected function mapProviderRoutes(): void
    {
        $path = module_path('Orders', 'Routes/provider.php');

        if (! file_exists($path)) {
            return;
        }

        Route::group([
            'prefix' => LaravelLocalization::setLocale(),
            'middleware' => ['web', 'localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
        ], function () use ($path) {
            Route::group(['prefix' => 'provider', 'as' => 'provider.'], function () use ($path) {
                Route::middleware('auth:provider')->group(function () use ($path) {
                    Route::prefix('dashboard')->group($path);
                });
            });
        });
    }
}
