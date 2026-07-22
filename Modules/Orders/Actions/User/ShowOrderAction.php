<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ShowOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, User $user): Order
    {
        if ($order->user()->isNot($user)) {
            abort(404);
        }

        return $this->orders->loadForUserShow($order);
    }
}
