<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Events\PaymentFailed;

class NotifyOrderPaymentFailed implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        // TODO: notify user that payment failed
    }
}
