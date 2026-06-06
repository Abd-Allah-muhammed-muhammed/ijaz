<?php

namespace Modules\Opportunity\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Opportunity';

    public function boot(): void
    {
        $this->map();
    }
}
