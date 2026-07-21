<?php

namespace Modules\Support\Providers;

use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Support\Contracts\Repositories\TicketSupportRepositoryInterface;
use Modules\Support\Contracts\Services\TicketSupportServiceInterface;
use Modules\Support\Handlers\TicketSupportChatHandler;
use Modules\Support\Repositories\TicketSupportRepository;
use Modules\Support\Services\TicketSupportService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SupportServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Support';

    protected string $nameLower = 'support';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(TicketSupportRepositoryInterface::class, TicketSupportRepository::class);
        $this->app->bind(TicketSupportServiceInterface::class, TicketSupportService::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(ChatTypeRegistry::class)
            ->register(ChatTypeEnum::TicketSupport, new TicketSupportChatHandler);
    }
}
