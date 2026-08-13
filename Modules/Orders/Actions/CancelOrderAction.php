<?php

namespace Modules\Orders\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CancelOrderPaymentAction as ReverseOrderPaymentHolds;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\CancelOrderDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderCancelledNotification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CancelOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ReverseOrderPaymentHolds $reverseOrderPaymentHolds,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, ?Authenticatable $actor, CancelOrderDTO $data): void
    {
        DB::transaction(function () use ($order, $actor, $data): void {
            $order = $this->orders->lockForUpdate($order);

            $isUser = $actor !== null && $order->user()->is($actor);
            $isProvider = $actor !== null && $order->provider()->is($actor);

            if (! $isUser && ! $isProvider) {
                abort(404);
            }

            $actorRole = $isUser ? 'user' : 'provider';
            $nextStatus = $isUser
                ? OrderStatusEnum::CancelledByClient
                : OrderStatusEnum::CancelledByProvider;

            if (! OrderStatusEnum::isAllowed($order->status, $nextStatus, $actorRole)) {
                throw new OrdersException('you can not cancel this order', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $order = $this->orders->update($order, [
                'status' => $nextStatus,
                'cancellation_reason' => $data->reason,
                'cancelled_at' => now(),
            ]);

            $this->reverseOrderPaymentHolds->handle($order);

            $order->loadMissing(['user', 'provider']);
            $otherParty = $isUser ? $order->provider : $order->user;
            $otherParty?->notify(new OrderCancelledNotification($order));
        });
    }
}
