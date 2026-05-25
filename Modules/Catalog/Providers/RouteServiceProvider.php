<?php

namespace Modules\Catalog\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Catalog';

    public function boot(): void
    {
        $this->map();
    }
}
