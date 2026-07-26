<?php

namespace Modules\Settings\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Settings';

    public function boot(): void
    {
        $this->map();
    }
}
