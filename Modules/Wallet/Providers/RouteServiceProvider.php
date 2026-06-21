<?php

namespace Modules\Wallet\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Wallet';

    public function boot(): void
    {
        $this->mapApiRoutes('V1', 'api/v1', 'api.v1.');
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

        Route::middleware(['web', 'auth:provider'])
            ->group($path);
    }

    protected function mapDashboardRoutes(): void
    {
        $path = module_path('Wallet', 'Routes/dashboard.php');

        if (! file_exists($path)) {
            return;
        }

        Route::middleware(['web', 'auth:admin'])
            ->group($path);
    }
}
