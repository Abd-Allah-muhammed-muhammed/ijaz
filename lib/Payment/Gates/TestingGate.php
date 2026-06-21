<?php

namespace Lib\Payment\Gates;

use Modules\Payment\Models\Payment;
use http\Exception\RuntimeException;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\DTOs\PaymentResponse;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class TestingGate implements IPaymentGate
{
    public function pay(Payment $payment): PaymentResponse
    {
        $url = in_array($payment->product_type, [
            GuarantorRequest::class,
            GuarantorInstallment::class,
        ], true)
            ? route('payment.paytabs.guarantor.redirect', $payment)
            : route('payment.test', ['payment' => $payment->id]);

        return new PaymentResponse(
            status: 'success',
            transactionId: uniqid('test-transaction-id', true),
            driver: 'testing',
            url: $url,
            payable: true,
            data: [
                'amount' => $payment->amount,
                'description' => 'Test payment',
                'payment_id' => $payment->id,
            ],
            message: null
        );
    }

    public function get(string $transactionId): PaymentResponse
    {
        return new PaymentResponse(
            status: 'success',
            transactionId: $transactionId,
            driver: 'testing',
            url: '',
            payable: true,
            data: [
                'amount' => 100,
                'description' => 'Test payment',
            ],
            message: null
        );
    }

    public function refund(string $transactionId): PaymentResponse
    {
        throw new RuntimeException('unimplemented method refund() in TestingGate');
    }
}
