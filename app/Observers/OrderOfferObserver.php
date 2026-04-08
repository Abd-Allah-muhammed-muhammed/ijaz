<?php

namespace App\Observers;

use App\Models\OrderOffer;

class OrderOfferObserver
{
    /**
     * Handle the OrderOffer "created" event.
     */
    public function created(OrderOffer $orderOffer): void
    {
        //
    }

    /**
     * Handle the OrderOffer "updated" event.
     */
    public function updated(OrderOffer $orderOffer): void
    {
        if ($orderOffer->isDirty('status', 'price', 'description')) {
            $orderOffer->histories()->create($orderOffer->replicateQuietly()->toArray());
        }
    }

    /**
     * Handle the OrderOffer "deleted" event.
     */
    public function deleted(OrderOffer $orderOffer): void
    {
        //
    }

    /**
     * Handle the OrderOffer "restored" event.
     */
    public function restored(OrderOffer $orderOffer): void
    {
        //
    }

    /**
     * Handle the OrderOffer "force deleted" event.
     */
    public function forceDeleted(OrderOffer $orderOffer): void
    {
        //
    }
}
