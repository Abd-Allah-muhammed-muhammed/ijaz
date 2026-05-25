<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

abstract class BaseModuleRouteServiceProvider extends ServiceProvider
{
    protected string $moduleName;

    public function map(): void
    {
        $this->mapApiRoutes('V1', 'api/v1', 'api.v1.');
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