<?php

namespace Modules\Guarantor\Providers;

use App\Providers\BaseModuleRouteServiceProvider;
use App\Support\Api\ApiVersionRegistry;

class RouteServiceProvider extends BaseModuleRouteServiceProvider
{
    protected string $moduleName = 'Guarantor';

    public function boot(): void
    {
        $version = app(ApiVersionRegistry::class)->default();

        $this->additionalApiRoutes = [
            'Routes/'.$version->folder.'/chat.php' => [
                'prefix' => $version->prefix.'/chats/guarantor',
                'name' => $version->name.'chats.guarantor.',
            ],
        ];

        $this->map();
    }
}
