<?php

namespace Modules\Classifieds\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Classifieds';

    public function boot(): void
    {
        $this->map();
    }
}
