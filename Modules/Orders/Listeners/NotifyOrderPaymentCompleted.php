<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Events\PaymentCompleted;

class NotifyOrderPaymentCompleted implements ShouldQueue
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        // TODO: send push notifications to user + provider
        // Replaces the old NotifyUserForOrder + NotifyProviderForOrder no-ops
        // Implementation: FirebaseService::notify() in a future phase
    }
}
