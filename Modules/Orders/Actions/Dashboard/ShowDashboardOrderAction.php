<?php

namespace Modules\Orders\Actions\Dashboard;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ShowDashboardOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order): Order
    {
        return $this->orders->loadForDashboardShow($order);
    }
}
