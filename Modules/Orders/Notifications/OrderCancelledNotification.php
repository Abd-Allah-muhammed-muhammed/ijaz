<?php

namespace Modules\Orders\Notifications;

use App\Models\Provider;
use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Orders\Models\Order;

class OrderCancelledNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Order $order) {}

    protected function titleKey(): string
    {
        return 'order_cancelled';
    }

    protected function bodyKey(): string
    {
        return 'order_has_been_cancelled';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'final_status' => $this->order->status->value,
            'cancellation_reason' => $this->order->cancellation_reason,
        ];
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'final_status' => $this->order->status->value,
            'cancellation_reason' => $this->order->cancellation_reason,
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User || $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return 'order cancelled';
    }
}
