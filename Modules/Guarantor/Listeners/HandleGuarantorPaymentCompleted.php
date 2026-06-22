<?php

namespace Modules\Guarantor\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Events\PaymentCompleted;

class HandleGuarantorPaymentCompleted
{
    public function __construct(
        private readonly ProcessGuarantorPayment $processGuarantorPayment,
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

        DB::transaction(function () use ($payment) {
            $this->processGuarantorPayment->handle($payment);
        });
    }
}
