<?php

namespace Modules\Guarantor\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Guarantor';

    public function boot(): void
    {
        $this->map();
        $this->mapChatRoutes();
    }

    protected function mapChatRoutes(): void
    {
        $chatRoutesPath = module_path($this->moduleName, 'Routes/V1/chat.php');

        if (! is_file($chatRoutesPath)) {
            return;
        }

        Route::middleware('api')
            ->prefix('api/v1')
            ->name('api.v1.')
            ->group($chatRoutesPath);
    }
}
