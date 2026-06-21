<?php

namespace Modules\Guarantor\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Guarantor\Actions\Payment\NotifyGuarantorPayment;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Events\PaymentCompleted;

class NotifyGuarantorPaymentCompleted implements ShouldQueue
{
    public function __construct(
        private readonly NotifyGuarantorPayment $notifyGuarantorPayment,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if (! in_array($payment->product_type, [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ], true)) {
            return;
        }

        $this->notifyGuarantorPayment->handle($payment);
    }
}
