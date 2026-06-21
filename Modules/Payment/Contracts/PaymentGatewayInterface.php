<?php

namespace Modules\Payment\Contracts;

use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initiate a payment — create hosted page, return URL.
     */
    public function initiate(Payment $payment): PaymentInitResult;

    /**
     * Verify a payment after callback — query gateway for result.
     */
    public function verify(Payment $payment, array $payload): PaymentVerifyResult;

    /**
     * Return active config for this gateway (based on current mode).
     */
    public function getConfig(): array;
}
