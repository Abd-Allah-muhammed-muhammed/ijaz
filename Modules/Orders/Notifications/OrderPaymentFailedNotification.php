<?php

namespace Modules\Orders\Notifications;

use App\Models\User;
use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

class OrderPaymentFailedNotification extends DomainNotification implements ShouldBroadcastNow
{
    public function __construct(
        public Order $order,
        public OrderOffer $offer,
    ) {}

    protected function titleKey(): string
    {
        return 'order_payment_failed';
    }

    protected function bodyKey(): string
    {
        return 'order_payment_failed_body';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->offer->id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'offer_id' => $this->offer->id,
            'screen' => 'orders',
        ];
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $notifiable instanceof User;
    }

    public function broadcastType(): string
    {
        return 'order payment failed';
    }
}
