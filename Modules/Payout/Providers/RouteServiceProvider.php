<?php

namespace Modules\Payout\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Payout';

    public function boot(): void
    {
        $this->map();
    }
}
