<?php

namespace Modules\Wallet\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Models\TopUpRequest;

class NotifyTopUpPaymentFailed implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== TopUpRequest::class) {
            return;
        }

        // TODO: notify user that top-up payment failed
        // FirebaseService::notify($payment->user, 'top_up_failed', ...)
    }
}
