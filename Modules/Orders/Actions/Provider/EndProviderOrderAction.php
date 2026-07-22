<?php

namespace Modules\Orders\Actions\Provider;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpFoundation\Response;

class EndProviderOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, ?Authenticatable $authUser): void
    {
        // KNOWN BUG: see Orders Step 2 — ownership uses auth()->user() (default guard) instead of
        // auth('provider')->user(); the caller passes the default-guard user verbatim.
        if ($order->provider()->isNot($authUser)) {
            abort(404);
        }

        if ($order->status->isNotIn([OrderStatusEnum::InProgress])) {
            throw new OrdersException('you can not ed this order', Response::HTTP_BAD_REQUEST);
        }

        $this->orders->update($order, ['status' => OrderStatusEnum::EndedByProvider]);
    }
}
