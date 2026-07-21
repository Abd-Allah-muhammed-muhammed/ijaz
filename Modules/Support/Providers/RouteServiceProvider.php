<?php

namespace Modules\Support\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Support';

    public function boot(): void
    {
        $this->map();
    }
}
