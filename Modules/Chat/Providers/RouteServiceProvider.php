<?php

namespace Modules\Chat\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Chat';

    public function boot(): void
    {
        $this->map();
        $this->mapProviderRoutes();
        $this->mapChatApiRoutes();
    }

    protected function mapChatApiRoutes(): void
    {
        $path = module_path('Chat', 'Routes/V1/chat.php');

        if (file_exists($path)) {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group($path);
        }
    }

    protected function mapProviderRoutes(): void
    {
        $path = module_path('Chat', 'Routes/provider.php');

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
