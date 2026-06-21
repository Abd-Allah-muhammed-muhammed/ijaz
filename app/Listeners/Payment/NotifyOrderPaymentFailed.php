<?php

namespace App\Listeners\Payment;

use App\Models\OrderOffer;
use Illuminate\Contracts\Queue\ShouldQueue;
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
