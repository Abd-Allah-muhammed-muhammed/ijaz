<?php

namespace Modules\Chat\Providers;

use Modules\Chat\Contracts\IChatService;
use Modules\Chat\Infrastructure\Jobs\NotifyChatMessageReceiver;
use Modules\Chat\Services\ChatService;
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

        $this->app->bind(
            IChatService::class,
            ChatService::class,
        );

        // Backward compat
        $this->app->bind(
            'App\Services\Chat\Contracts\IChatService',
            ChatService::class,
        );

        // Queue safety: jobs in flight use old namespace
        if (! class_exists('App\Services\Chat\Jobs\NotifyChatMessageReceiver', false)) {
            class_alias(
                NotifyChatMessageReceiver::class,
                'App\Services\Chat\Jobs\NotifyChatMessageReceiver'
            );
        }

        // Repository bindings — Phase 5.6
    }

    public function boot(): void
    {
        parent::boot();

        // Policies — Phase 7
        // Handlers — Phase 4
    }
}
