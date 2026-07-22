<?php

namespace Modules\Orders\Observers;

use Modules\Orders\Models\OrderOffer;

class OrderOfferObserver
{
    public function created(OrderOffer $orderOffer): void
    {
        //
    }

    public function updated(OrderOffer $orderOffer): void
    {
        if ($orderOffer->isDirty('status', 'price', 'description')) {
            $orderOffer->histories()->create($orderOffer->replicateQuietly()->toArray());
        }
    }

    public function deleted(OrderOffer $orderOffer): void
    {
        //
    }

    public function restored(OrderOffer $orderOffer): void
    {
        //
    }

    public function forceDeleted(OrderOffer $orderOffer): void
    {
        //
    }
}
