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

    protected function mapProviderRoutes(): void
    {
        $path = module_path('Orders', 'Routes/provider.php');

        if (! file_exists($path)) {
            return;
        }

        Route::group([
            'prefix' => LaravelLocalization::setLocale(),
            'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
        ], function () use ($path) {
            Route::group(['prefix' => 'provider', 'as' => 'provider.'], function () use ($path) {
                Route::middleware('auth:provider')->group(function () use ($path) {
                    Route::prefix('dashboard')->group($path);
                });
            });
        });
    }
}
