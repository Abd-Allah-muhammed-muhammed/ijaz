<?php

namespace Modules\Orders\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Orders';

    public function boot(): void
    {
        $this->map();
    }
}
