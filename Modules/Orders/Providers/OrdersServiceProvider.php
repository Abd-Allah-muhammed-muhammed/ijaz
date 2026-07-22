<?php

namespace Modules\Orders\Providers;

use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Repositories\OrderOfferRepository;
use Modules\Orders\Repositories\OrderRepository;
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
    }
}
