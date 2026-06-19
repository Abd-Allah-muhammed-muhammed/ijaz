<?php

namespace Modules\Chat\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class ChatServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Chat';

    protected string $nameLower = 'chat';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Repository bindings — Phase 5.6
        // Class aliases — Phase 2
    }

    public function boot(): void
    {
        parent::boot();

        // Policies — Phase 7
        // Handlers — Phase 4
    }
}
