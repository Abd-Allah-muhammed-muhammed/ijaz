<?php

namespace Modules\Support\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Support';

    public function boot(): void
    {
        $this->map();
    }

    /**
     * Override: load Routes/V1/api.php WITHOUT the default api.v1.{module}.
     * name prefix, since these ticket routes were previously unnamed and must
     * stay that way (same lesson as Cms catalog routes).
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
}
