<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Modules\Orders\Models\Order;

class NewOrderAssignNotification extends DomainNotification implements ShouldBroadcastNow
{
    public function __construct(public Order $order) {}

    protected function titleKey(): string
    {
        return 'new_order_assigned';
    }

    protected function bodyKey(): string
    {
        return 'you_have_been_assigned_a_new_order';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'screen' => 'orders',
        ];
    }

    public function broadcastType(): string
    {
        return 'new assigned order';
    }
}
