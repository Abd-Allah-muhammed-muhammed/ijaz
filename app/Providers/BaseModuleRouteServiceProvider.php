<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Base route service provider for all Modules/*.
 *
 * ## Extending map() vs mapApiRoutes()
 *
 * If a module needs custom route-loading behavior (a different file name, no
 * name prefix, a different middleware/prefix combo, etc.), override the
 * SPECIFIC method responsible for that behavior — usually mapApiRoutes() —
 * not map() itself. map() is the orchestrator; keep it untouched in every
 * module's RouteServiceProvider so the overall route-loading sequence stays
 * predictable and consistent across the whole project.
 *
 * Example (correct — override mapApiRoutes() only):
 * ```php
 * protected function mapApiRoutes(string $version, string $prefix, string $namePrefix): void
 * {
 *     $routesPath = module_path($this->moduleName, 'Routes/'.$version.'/api.php');
 *     if (! is_file($routesPath)) {
 *         return;
 *     }
 *     Route::middleware('api')->prefix($prefix)->group($routesPath);
 *     // no ->name() call — preserves unprefixed route names
 * }
 * ```
 *
 * ## The double-registration trap
 *
 * A module that (a) keeps the default map() AND (b) ALSO manually registers
 * `Routes/V1/api.php` via $additionalApiRoutes will have that file loaded
 * TWICE — once by the default mapApiRoutes() (with an auto-generated
 * `api.v1.{module}.` name prefix) and once via $additionalApiRoutes (with
 * whatever name it specifies). This happened in Modules/Cms and Modules/Jobs
 * and was fixed by overriding mapApiRoutes() directly instead (see git history
 * on this file's usage in those modules for the before/after).
 *
 * $additionalApiRoutes remains useful for genuinely ADDITIONAL route files
 * beyond the default Routes/V1/api.php (e.g. Guarantor/Opportunity's
 * Routes/V1/chat.php) — just don't use it to re-register the SAME file the
 * default mapApiRoutes() already handles.
 *
 * ## Modules with intentionally different structures (fine, not bugs)
 *
 * Some modules (Wallet, Payment) use entirely different file names
 * (Routes/V1/wallet.php, Routes/api.php) or custom boot() orchestration
 * instead of map(). This is fine and does NOT need to be "fixed" for
 * consistency — there's no double-registration risk when the file name
 * itself differs from what the default mapApiRoutes() looks for
 * (Routes/V1/api.php). Only "fix" a module's route provider if there's an
 * ACTUAL bug (verify via `php artisan route:list` showing duplicate URIs),
 * not for stylistic uniformity alone.
 */
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
