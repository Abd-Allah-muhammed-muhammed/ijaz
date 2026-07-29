<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ShowProviderOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, Provider $provider): Order
    {
        return $this->orders->loadForProviderShow($order, $provider);
    }
}
