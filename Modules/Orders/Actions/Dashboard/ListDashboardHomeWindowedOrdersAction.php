<?php

namespace Modules\Orders\Actions\Dashboard;

use Illuminate\Support\Collection;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ListDashboardHomeWindowedOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function handle(): Collection
    {
        return $this->orders->listWindowedForDashboardHome();
    }
}
