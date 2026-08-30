<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderPaymentCompletedNotification;
use Modules\Payment\Events\PaymentCompleted;

class NotifyOrderPaymentCompleted implements ShouldQueue
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        $offer = $payment->product;
        $order = $offer->order;
        $order->loadMissing(['user', 'provider']);

        $notification = new OrderPaymentCompletedNotification($order);

        $order->user->notify($notification);

        if ($order->provider !== null) {
            $order->provider->notify($notification);
        }
    }
}
