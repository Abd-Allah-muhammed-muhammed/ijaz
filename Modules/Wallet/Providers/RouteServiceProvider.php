<?php

namespace Modules\Wallet\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use App\Support\Api\ApiVersionRegistry;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Wallet';

    public function boot(): void
    {
        foreach (app(ApiVersionRegistry::class)->enabled() as $version) {
            $this->mapApiRoutes($version->folder, $version->prefix, $version->name);
        }

        $this->mapProviderRoutes();
        $this->mapDashboardRoutes();
    }

    protected function mapApiRoutes(string $version, string $prefix, string $namePrefix): void
    {
        $path = module_path('Wallet', 'Routes/'.$version.'/wallet.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware('api')
            ->prefix($prefix)
            ->group($path);
    }

    protected function mapProviderRoutes(): void
    {
        $path = module_path('Wallet', 'Routes/provider.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware('web')->group(function () use ($path) {
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
        });
    }

    protected function mapDashboardRoutes(): void
    {
        $path = module_path('Wallet', 'Routes/dashboard.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware('web')->group(function () use ($path) {
            Route::group([
                'prefix' => LaravelLocalization::setLocale(),
                'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
            ], function () use ($path) {
                Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () use ($path) {
                    Route::middleware('auth:admin')->group($path);
                });
            });
        });
    }
}
