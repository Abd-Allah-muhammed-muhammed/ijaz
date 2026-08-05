<?php

namespace Modules\Chat\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use App\Support\Api\ApiVersionRegistry;
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
        foreach (app(ApiVersionRegistry::class)->enabled() as $version) {
            $path = module_path('Chat', 'Routes/'.$version->folder.'/chat.php');

            if (! file_exists($path)) {
                continue;
            }

            Route::middleware('api')
                ->prefix($version->prefix)
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
