<?php

namespace Modules\Orders\Actions\Offer;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class RevertOrderToNewAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order): void
    {
        $this->orders->update($order, [
            'provider_id' => null,
            'accepted_offer_id' => null,
            'status' => OrderStatusEnum::New,
            'price' => null,
        ]);
    }
}
