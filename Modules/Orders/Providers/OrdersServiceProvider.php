<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Orders\Console\Commands\AlertUnsettledOrderSettlementsCommand;
use Modules\Orders\Console\Commands\SettleCompletedOrdersCommand;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Handlers\OrderChatHandler;
use Modules\Orders\Listeners\HandleOrderPaymentCompleted;
use Modules\Orders\Listeners\HandleOrderPaymentFailed;
use Modules\Orders\Listeners\NotifyOrderPaymentCompleted;
use Modules\Orders\Listeners\NotifyOrderPaymentFailed;
use Modules\Orders\Models\Order;
use Modules\Orders\Policies\OrderPolicy;
use Modules\Orders\Repositories\OrderOfferRepository;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Nwidart\Modules\Support\ModuleServiceProvider;

class OrdersServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Orders';

    protected string $nameLower = 'orders';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderOfferRepositoryInterface::class, OrderOfferRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Order::class, OrderPolicy::class);

        Event::listen(PaymentCompleted::class, HandleOrderPaymentCompleted::class);
        Event::listen(PaymentCompleted::class, NotifyOrderPaymentCompleted::class);
        Event::listen(PaymentFailed::class, HandleOrderPaymentFailed::class);
        Event::listen(PaymentFailed::class, NotifyOrderPaymentFailed::class);

        $this->app->make(ChatTypeRegistry::class)
            ->register(ChatTypeEnum::Order, new OrderChatHandler);

        if ($this->app->runningInConsole()) {
            $this->commands([
                SettleCompletedOrdersCommand::class,
                AlertUnsettledOrderSettlementsCommand::class,
            ]);
        }
    }
}
