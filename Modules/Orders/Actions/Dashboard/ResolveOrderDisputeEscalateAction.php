<?php

namespace Modules\Orders\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CancelOrderPaymentAction;
use Modules\Orders\Actions\LogOrderStatusHistoryAction as LogOrderStatusHistory;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderDisputeResolutionEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderDisputeResolvedNotification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ResolveOrderDisputeEscalateAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LogOrderStatusHistory $logStatusHistory,
        private readonly CancelOrderPaymentAction $cancelOrderPaymentAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Order $order,
        Admin $admin,
        ?string $notes = null,
    ): Order {
        return DB::transaction(function () use ($order, $admin, $notes) {
            $order = $this->orderRepository->lockForUpdate($order);

            if ($order->status->isNot(OrderStatusEnum::Disputed)) {
                throw new OrdersException('order.dispute_already_resolved', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $fromStatus = $order->status->value;
            $resolution = OrderDisputeResolutionEnum::Escalate;

            $this->cancelOrderPaymentAction->handle($order);

            $order = Order::withoutEvents(
                fn () => $this->orderRepository->update($order, [
                    'status' => OrderStatusEnum::Escalated,
                ])
            );

            $this->logStatusHistory->handle(
                $order,
                $admin,
                $fromStatus,
                OrderStatusEnum::Escalated->value,
                reason: $resolution->historyReason(),
                notes: $notes,
            );

            $order->loadMissing(['user', 'provider']);
            $notification = new OrderDisputeResolvedNotification($order, $resolution);
            $order->user?->notify($notification);
            $order->provider?->notify($notification);

            return $order->fresh(['user', 'provider', 'acceptedOffer', 'histories']);
        });
    }
}
