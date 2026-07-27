<?php

namespace Modules\Reviews\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Reviews';

    public function boot(): void
    {
        $this->map();
    }
}
