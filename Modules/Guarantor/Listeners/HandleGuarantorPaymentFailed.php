<?php

namespace Modules\Guarantor\Listeners;

use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Events\PaymentFailed;

class HandleGuarantorPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if (! in_array($payment->product_type, [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ], true)) {
            return;
        }

        // Currently no domain changes on failure — placeholder for future logic
        // e.g. notify parties, update status to payment_failed
    }
}
