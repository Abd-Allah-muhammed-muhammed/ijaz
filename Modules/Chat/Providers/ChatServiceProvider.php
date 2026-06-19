<?php

namespace Modules\Chat\Providers;

use Modules\Chat\Contracts\IChatService;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Handlers\GuarantorChatHandler;
use Modules\Chat\Handlers\MemberChatHandler;
use Modules\Chat\Handlers\OpportunityChatHandler;
use Modules\Chat\Handlers\OrderChatHandler;
use Modules\Chat\Handlers\TicketSupportChatHandler;
use Modules\Chat\Infrastructure\Jobs\NotifyChatMessageReceiver;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Chat\Repositories\ConversationMessageRepository;
use Modules\Chat\Repositories\ConversationRepository;
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

        if (! class_exists('App\Services\Chat\Jobs\NotifyChatMessageReceiver', false)) {
            class_alias(
                NotifyChatMessageReceiver::class,
                'App\Services\Chat\Jobs\NotifyChatMessageReceiver'
            );
        }

        $this->app->bind(
            IChatService::class,
            ChatService::class,
        );

        $this->app->bind(
            'App\Services\Chat\Contracts\IChatService',
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

        $registry = $this->app->make(ChatTypeRegistry::class);

        $registry->register(ChatTypeEnum::Member, new MemberChatHandler);
        $registry->register(ChatTypeEnum::Order, new OrderChatHandler);
        $registry->register(ChatTypeEnum::TicketSupport, new TicketSupportChatHandler);
        $registry->register(ChatTypeEnum::Opportunity, new OpportunityChatHandler);
        $registry->register(ChatTypeEnum::Guarantor, new GuarantorChatHandler);

        // Policies — Phase 7
    }
}
