<?php

namespace Modules\Chat\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Chat';

    public function boot(): void
    {
        $this->map();
        $this->mapProviderRoutes();
        $this->mapDashboardRoutes();
        $this->mapChatApiRoutes();
    }

    protected function mapChatApiRoutes(): void
    {
        $path = module_path('Chat', 'Routes/V1/chat.php');

        if (file_exists($path)) {
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group($path);
        }
    }

    protected function mapProviderRoutes(): void
    {
        $path = module_path('Chat', 'Routes/provider.php');

        if (file_exists($path)) {
            Route::middleware(['web', 'auth:provider'])
                ->group($path);
        }
    }

    protected function mapDashboardRoutes(): void
    {
        $path = module_path('Chat', 'Routes/dashboard.php');

        if (file_exists($path)) {
            Route::middleware(['web', 'auth:admin'])
                ->group($path);
        }
    }
}
