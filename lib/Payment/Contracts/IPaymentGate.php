<?php

namespace Lib\Payment\Contracts;

use Modules\Payment\Models\Payment;
use Lib\Payment\DTOs\PaymentResponse;

interface IPaymentGate
{
    public function pay(Payment $payment): PaymentResponse;

    public function get(string $transactionId): PaymentResponse;

    public function refund(string $transactionId): PaymentResponse;
}
