<?php

namespace Modules\Opportunity\Providers;

use App\Providers\BaseModuleRouteServiceProvider;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Opportunity';

    protected array $additionalApiRoutes = [
        'Routes/V1/chat.php' => [
            'prefix' => 'api/v1/chats/opportunities',
            'name' => 'api.v1.chats.opportunities.',
        ],
    ];

    public function boot(): void
    {
        $this->map();
    }
}
