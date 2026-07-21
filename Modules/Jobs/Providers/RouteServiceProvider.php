<?php

namespace Modules\Jobs\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Jobs';

    /**
     * Jobs routes must keep unprefixed names (jobs.index, etc.).
     * Default mapApiRoutes() would apply api.v1.jobs. and produce jobs.jobs.*.
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
