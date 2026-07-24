<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\Facades\Event;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Listeners\HandleOrderPaymentCompleted;
use Modules\Orders\Listeners\HandleOrderPaymentFailed;
use Modules\Orders\Listeners\NotifyOrderPaymentCompleted;
use Modules\Orders\Listeners\NotifyOrderPaymentFailed;
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

        Event::listen(PaymentCompleted::class, HandleOrderPaymentCompleted::class);
        Event::listen(PaymentCompleted::class, NotifyOrderPaymentCompleted::class);
        Event::listen(PaymentFailed::class, HandleOrderPaymentFailed::class);
        Event::listen(PaymentFailed::class, NotifyOrderPaymentFailed::class);
    }
}
