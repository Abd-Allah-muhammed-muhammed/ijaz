<?php

namespace Modules\Orders\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Orders\Models\Order;

class OrderEndedByProviderNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public Order $order) {}

    protected function titleKey(): string
    {
        return 'order_ended_by_provider';
    }

    protected function bodyKey(): string
    {
        return 'order_has_been_ended_by_provider';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'final_status' => $this->order->status->value,
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
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'order ended by provider';
    }
}
