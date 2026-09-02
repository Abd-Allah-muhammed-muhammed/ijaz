<?php

namespace Modules\Orders\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CancelOrderPaymentAction;
use Modules\Orders\Actions\LogOrderStatusHistoryAction as LogOrderStatusHistory;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderCancelledNotification;
use Modules\Orders\Support\OrderDisputeHistoryReason;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CancelOrderDuringDisputeAction
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
        string $reason,
        ?string $notes,
        Admin $admin,
    ): void {
        DB::transaction(function () use ($order, $reason, $notes, $admin): void {
            $order = $this->orderRepository->lockForUpdate($order);

            if ($order->status->isIn([
                OrderStatusEnum::Cancelled,
                OrderStatusEnum::CancelledViaDispute,
                OrderStatusEnum::CancelledByProvider,
                OrderStatusEnum::CancelledByClient,
                OrderStatusEnum::EndedByProvider,
                OrderStatusEnum::EndedByClient,
                OrderStatusEnum::EndedViaDispute,
                OrderStatusEnum::Escalated,
                OrderStatusEnum::Settled,
            ])) {
                throw new OrdersException('order.status_transition_not_allowed', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $wasDisputed = $order->status->is(OrderStatusEnum::Disputed);
            $fromStatus = $order->status->value;

            $order = Order::withoutEvents(
                fn () => $this->orderRepository->update($order, [
                    'status' => OrderStatusEnum::Cancelled,
                    'cancellation_reason' => $reason,
                    'cancelled_at' => now(),
                ])
            );

            $this->logStatusHistory->handle(
                $order,
                $admin,
                $fromStatus,
                OrderStatusEnum::Cancelled->value,
                reason: $reason,
                notes: $notes,
            );

            if ($wasDisputed) {
                $this->logStatusHistory->handle(
                    $order,
                    $admin,
                    OrderStatusEnum::Cancelled->value,
                    OrderStatusEnum::Cancelled->value,
                    reason: OrderDisputeHistoryReason::ClosedByAdminCancel,
                    notes: $notes,
                );
            }

            $this->cancelOrderPaymentAction->handle($order->fresh());

            $order->loadMissing(['user', 'provider']);
            $order->user?->notify(new OrderCancelledNotification($order));
            $order->provider?->notify(new OrderCancelledNotification($order));
        });
    }
}
