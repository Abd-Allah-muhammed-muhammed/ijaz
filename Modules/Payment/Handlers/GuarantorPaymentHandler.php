<?php

namespace Modules\Payment\Handlers;

use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Contracts\PaymentHandlerInterface;
use Modules\Payment\Models\Payment;

class GuarantorPaymentHandler implements PaymentHandlerInterface
{
    public function onSuccess(Payment $payment): void
    {
        // Intentionally empty:
        // Guarantor domain logic handled by GuarantorServiceProvider listener
        // listening to PaymentCompleted event
    }

    public function onFailure(Payment $payment): void
    {
        // Intentionally empty:
        // Handled by PaymentFailed event listener in Guarantor module
    }

    public function productTypes(): array
    {
        return [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ];
    }
}
