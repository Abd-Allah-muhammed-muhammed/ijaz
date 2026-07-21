<?php

namespace Modules\Chat\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Chat\Contracts\IChatService;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Handlers\OrderChatHandler;
use Modules\Chat\Infrastructure\Jobs\NotifyChatMessageReceiver;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Policies\ConversationPolicy;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Chat\Repositories\ConversationMessageRepository;
use Modules\Chat\Repositories\ConversationRepository;
use Modules\Chat\Services\ChatService;
use Modules\Chat\Services\ConversationService;
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

        // TODO: Remove this alias after confirming no in-flight jobs
        // with old namespace App\Services\Chat\Jobs\NotifyChatMessageReceiver.
        // Check queue: php artisan queue:monitor
        if (! class_exists('App\Services\Chat\Jobs\NotifyChatMessageReceiver', false)) {
            class_alias(
                NotifyChatMessageReceiver::class,
                'App\Services\Chat\Jobs\NotifyChatMessageReceiver'
            );
        }

        $this->app->singleton(ConversationService::class);

        $this->app->bind(
            IChatService::class,
            ChatService::class,
        );

        $this->app->singleton(
            ChatTypeRegistry::class,
            fn () => new ChatTypeRegistry,
        );

        $this->app->bind(
            ConversationRepositoryInterface::class,
            ConversationRepository::class,
        );

        $this->app->bind(
            ConversationMessageRepositoryInterface::class,
            ConversationMessageRepository::class,
        );
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->booted(function (): void {
            Gate::policy(Conversation::class, ConversationPolicy::class);
        });

        $registry = $this->app->make(ChatTypeRegistry::class);

        $registry->register(ChatTypeEnum::Member, new MemberChatHandler);
        $registry->register(ChatTypeEnum::Order, new OrderChatHandler);
    }
}
