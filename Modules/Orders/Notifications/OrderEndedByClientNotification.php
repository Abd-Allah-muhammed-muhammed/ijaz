<?php

namespace Modules\Orders\Notifications;

use App\Models\Provider;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Orders\Models\Order;

class OrderEndedByClientNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(
        public Order $order,
        public int $rating,
    ) {}

    protected function titleKey(): string
    {
        return 'order_ended_by_client';
    }

    protected function bodyKey(): string
    {
        return 'order_has_been_ended_by_client_with_review';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'final_status' => $this->order->status->value,
            'rating' => $this->rating,
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
            'rating' => (string) $this->rating,
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof Provider;
    }

    public function broadcastType(): string
    {
        return 'order ended by client';
    }
}
