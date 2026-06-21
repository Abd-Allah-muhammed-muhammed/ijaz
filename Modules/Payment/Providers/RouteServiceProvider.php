<?php

namespace Modules\Payment\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Payment';

    public function boot(): void
    {
        $this->map();
    }

    public function map(): void
    {
        $this->mapApiRoutes('', '', '');
        $this->mapWebRoutes();
    }

    protected function mapApiRoutes(string $version, string $prefix, string $namePrefix): void
    {
        $path = module_path('Payment', 'Routes/api.php');

        if (file_exists($path)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($path);
        }
    }

    protected function mapWebRoutes(): void
    {
        $path = module_path('Payment', 'Routes/web.php');

        if (file_exists($path)) {
            Route::middleware('web')
                ->group($path);
        }
    }
}
