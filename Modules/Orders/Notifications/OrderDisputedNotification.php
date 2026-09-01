<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Orders\Models\Order;
use Modules\Orders\Support\OrderFirebaseNotifiable;

class OrderDisputedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use OrderFirebaseNotifiable;

    public function __construct(
        public Order $order,
        public string $reason,
    ) {}

    protected function titleKey(): string
    {
        return 'order_disputed_title';
    }

    protected function bodyKey(): string
    {
        return 'order_disputed_body';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'reason' => $this->reason,
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
        return $this->orderPartyOrAdminReceivesFirebase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'order disputed';
    }
}
