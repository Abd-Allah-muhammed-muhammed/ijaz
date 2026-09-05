<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderPaymentFailedNotification;
use Modules\Payment\Events\PaymentFailed;

class NotifyOrderPaymentFailed implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        $payment->loadMissing(['product.order.user']);

        $offer = $payment->product;
        $order = $offer->order;

        $order->user->notify(new OrderPaymentFailedNotification($order, $offer));
    }
}
