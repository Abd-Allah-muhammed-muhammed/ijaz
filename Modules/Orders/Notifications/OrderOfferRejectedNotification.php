<?php

namespace Modules\Orders\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Orders\Models\OrderOffer;

class OrderOfferRejectedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    public function __construct(public OrderOffer $offer) {}

    protected function titleKey(): string
    {
        return 'order_offer_rejected';
    }

    protected function bodyKey(): string
    {
        return 'order_offer_has_been_rejected';
    }

    protected function payload(): array
    {
        return [
            'order_id' => $this->offer->order_id,
            'offer_id' => $this->offer->id,
        ];
    }

    protected function firebaseData(object $notifiable): array
    {
        return [];
    }

    public function broadcastType(): string
    {
        return 'order offer rejected';
    }
}
