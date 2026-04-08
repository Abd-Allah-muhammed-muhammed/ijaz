<?php

namespace Lib\Payment\Gates;

use App\Models\Payment;
use http\Exception\RuntimeException;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\DTOs\PaymentResponse;

class TestingGate implements IPaymentGate
{
    public function pay(Payment $payment): PaymentResponse
    {
        return new PaymentResponse(
            status: 'success',
            transactionId: uniqid('test-transaction-id', true),
            driver: 'testing',
            url: route('payment.test', ['payment' => $payment->id]),
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
