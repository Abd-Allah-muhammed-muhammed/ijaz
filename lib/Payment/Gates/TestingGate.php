<?php

namespace Lib\Payment\Gates;

use http\Exception\RuntimeException;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\DTOs\PaymentResponse;
use Modules\Payment\Models\Payment;

class TestingGate implements IPaymentGate
{
    public function pay(Payment $payment): PaymentResponse
    {
        $url = route('payment.redirect', [
            'driver' => 'testing',
            'payment' => $payment->id,
        ]);

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
