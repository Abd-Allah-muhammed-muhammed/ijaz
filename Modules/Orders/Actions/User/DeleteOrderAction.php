<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DeleteOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, User $user): void
    {
        if ($order->user()->isNot($user)) {
            abort(404);
        }

        if ($order->offers()->exists()) {
            throw new OrdersException('you can not delete this order because it has offers', Response::HTTP_BAD_REQUEST);
        }

        DB::transaction(function () use ($order) {
            $this->orders->delete($order);
        });
    }
}
