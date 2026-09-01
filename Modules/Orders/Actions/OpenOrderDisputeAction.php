<?php

namespace Modules\Orders\Actions;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\LogOrderStatusHistoryAction as LogOrderStatusHistory;
use Modules\Orders\Actions\NotifyAdminsOfOrderDisputedAction as NotifyAdminsOfOrderDisputed;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderDisputedNotification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OpenOrderDisputeAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LogOrderStatusHistory $logStatusHistory,
        private readonly NotifyAdminsOfOrderDisputed $notifyAdminsOfOrderDisputedAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Order $order,
        Authenticatable $actor,
        string $actorRole,
        string $reason,
    ): Order {
        return DB::transaction(function () use ($order, $actor, $actorRole, $reason) {
            $order = $this->orderRepository->lockForUpdate($order);

            if (! OrderStatusEnum::isAllowed($order->status, OrderStatusEnum::Disputed, $actorRole.'_dispute')) {
                throw new OrdersException('order.status_transition_not_allowed', Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $fromStatus = $order->status->value;

            $order = Order::withoutEvents(
                fn () => $this->orderRepository->update($order, [
                    'status' => OrderStatusEnum::Disputed,
                ])
            );

            $this->logStatusHistory->handle(
                $order,
                $actor instanceof Model ? $actor : $order->user,
                $fromStatus,
                OrderStatusEnum::Disputed->value,
                reason: $reason,
            );

            $order->loadMissing(['user', 'provider']);

            $otherParty = $this->otherParty($order, $actor);
            $otherParty?->notify(new OrderDisputedNotification($order, $reason));

            $this->notifyAdminsOfOrderDisputedAction->handle($order, $reason);

            return $order->fresh(['user', 'provider', 'acceptedOffer', 'histories']);
        });
    }

    private function otherParty(Order $order, Authenticatable $actor): User|Provider|null
    {
        if ($actor instanceof User && $order->user_id === $actor->getKey()) {
            return $order->provider;
        }

        if ($actor instanceof Provider && $order->provider_id === $actor->getKey()) {
            return $order->user;
        }

        return null;
    }
}
