<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

abstract class BaseModuleRouteServiceProvider extends ServiceProvider
{
    protected string $moduleName;

    /**
     * Extra route files beyond the default Routes/V1/api.php.
     *
     * @var array<string, array{
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>
     * }>
     */
    protected array $additionalApiRoutes = [];

    public function map(): void
    {
        $this->mapApiRoutes('V1', 'api/v1', 'api.v1.');
        $this->mapAdditionalApiRoutes();
        $this->mapDashboardRoutes();
    }

    protected function mapApiRoutes(string $version, string $prefix, string $namePrefix): void
    {
        $routesPath = module_path($this->moduleName, 'Routes/'.$version.'/api.php');

        if (! is_file($routesPath)) {
            return;
        }

        $moduleKey = Str::kebab($this->moduleName);

        Route::middleware('api')
            ->prefix($prefix)
            ->name($namePrefix.$moduleKey.'.')
            ->group($routesPath);
    }

    protected function mapAdditionalApiRoutes(): void
    {
        foreach ($this->additionalApiRoutes as $relativePath => $routeConfig) {
            $routesPath = module_path($this->moduleName, $relativePath);

            if (! is_file($routesPath)) {
                continue;
            }

            Route::middleware($routeConfig['middleware'] ?? 'api')
                ->prefix($routeConfig['prefix'] ?? 'api/v1')
                ->name($routeConfig['name'] ?? '')
                ->group($routesPath);
        }
    }

    protected function mapDashboardRoutes(): void
    {
        $routesPath = module_path($this->moduleName, 'Routes/dashboard.php');

        if (! is_file($routesPath)) {
            return;
        }

        Route::middleware('web')
            ->prefix(LaravelLocalization::setLocale().'/dashboard')
            ->name('dashboard.')
            ->group($routesPath);
    }
}
