<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\Models\Payment;

interface PaymentHandlerInterface
{
    /**
     * Called synchronously inside DB transaction after payment verified as Accepted.
     * Handle domain state updates (offer paid, top-up approved, etc.)
     */
    public function onSuccess(Payment $payment): void;

    /**
     * Called synchronously inside DB transaction after payment verified as Failed.
     */
    public function onFailure(Payment $payment): void;

    /**
     * Return the product types this handler handles.
     * e.g. [OrderOffer::class]
     */
    public function productTypes(): array;
}
