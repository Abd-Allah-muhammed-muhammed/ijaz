<?php

namespace Modules\Geo\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Geo';

    public function boot(): void
    {
        $this->map();
    }
}
