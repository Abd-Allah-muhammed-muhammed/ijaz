<?php

namespace App\Listeners\Payment;

use App\Models\OrderOffer;
use Modules\Payment\Events\PaymentFailed;

class HandleOrderPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        // No domain changes on failure — notification handled by NotifyOrderPaymentFailed
    }
}
