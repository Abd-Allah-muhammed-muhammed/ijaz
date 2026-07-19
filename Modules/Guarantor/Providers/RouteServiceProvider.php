<?php

namespace Modules\Guarantor\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Guarantor';

    protected array $additionalApiRoutes = [
        'Routes/V1/chat.php' => [
            'prefix' => 'api/v1/chats/guarantor',
            'name' => 'api.v1.chats.guarantor.',
        ],
    ];

    public function boot(): void
    {
        $this->map();
    }
}
