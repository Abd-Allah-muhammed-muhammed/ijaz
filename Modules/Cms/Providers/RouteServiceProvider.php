<?php

namespace Modules\Cms\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Cms';

    /**
     * Catalog CMS + message API routes were previously unnamed.
     * Default mapApiRoutes() would assign api.v1.cms. names — break parity.
     *
     * @var array<string, array{prefix?: string, name?: string, middleware?: string|array<int, string>}>
     */
    protected array $additionalApiRoutes = [
        'Routes/V1/api.php' => [
            'prefix' => 'api/v1',
            'name' => '',
            'middleware' => 'api',
        ],
    ];

    public function boot(): void
    {
        $this->map();
    }

    public function map(): void
    {
        // Skip mapApiRoutes() so Routes/V1/api.php is not double-loaded with a name prefix.
        $this->mapAdditionalApiRoutes();
        $this->mapDashboardRoutes();
    }
}
