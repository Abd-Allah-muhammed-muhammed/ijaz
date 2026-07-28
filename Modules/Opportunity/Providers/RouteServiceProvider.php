<?php

namespace Modules\Opportunity\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use App\Support\Api\ApiVersionRegistry;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Opportunity';

    public function boot(): void
    {
        $version = app(ApiVersionRegistry::class)->default();

        $this->additionalApiRoutes = [
            'Routes/'.$version->folder.'/chat.php' => [
                'prefix' => $version->prefix.'/chats/opportunities',
                'name' => $version->name.'chats.opportunities.',
            ],
        ];

        $this->map();
    }
}
