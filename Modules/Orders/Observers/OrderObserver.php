<?php

namespace Modules\Orders\Observers;

use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class OrderObserver
{
    public function created(Order $order): void
    {
        $order->histories()->create([
            'status' => OrderStatusEnum::New,
        ]);
    }

    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            $order->histories()->create([
                'status' => $order->status,
            ]);
        }
    }

    public function deleted(Order $order): void
    {
        //
    }

    public function restored(Order $order): void
    {
        //
    }

    public function forceDeleted(Order $order): void
    {
        //
    }
}
