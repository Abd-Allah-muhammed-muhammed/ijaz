<?php

namespace Modules\Settings\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Settings';

    public function boot(): void
    {
        $this->map();
    }

    /**
     * Override: load Routes/V1/api.php WITHOUT the default api.v1.settings.
     * name prefix, since catalog/settings was previously unnamed under
     * app PlatformController and must stay that way.
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
