<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Modules\Orders\Models\Order;

class OrderAcceptedOfferUpdatedNotification extends DomainNotification implements ShouldBroadcastNow
{
    public function __construct(public Order $order) {}

    protected function titleKey(): string
    {
        return 'order_accepted_offer_updated';
    }

    protected function bodyKey(): string
    {
        return 'the_order_accepted_offer_has_been_updated';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->order->accepted_offer_id,
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return true;
    }

    public function broadcastType(): string
    {
        return 'new assigned order';
    }
}
