<?php

namespace Modules\Guarantor\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Guarantor';

    public function boot(): void
    {
        $this->map();
    }
}
