<?php

namespace Modules\Marketplace\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Marketplace';

    public function boot(): void
    {
        $this->map();
    }
}
