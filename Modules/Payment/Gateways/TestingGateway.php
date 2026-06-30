<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class TestingGateway implements PaymentGatewayInterface
{
    public function getConfig(): array
    {
        return ['mode' => 'test', 'driver' => 'testing'];
    }

    public function initiate(Payment $payment): PaymentInitResult
    {
        $url = route('payment.redirect', [
            'driver' => 'testing',
            'payment' => $payment->id,
        ]);

        $payment->update([
            'request' => [
                'driver' => 'testing',
                'amount' => $payment->amount,
                'payment_id' => $payment->id,
                'redirect_url' => $url,
            ],
            'url' => $url,
        ]);

        return new PaymentInitResult(
            status: 'success',
            driver: 'testing',
            url: $url,
            payable: true,
            transactionId: uniqid('test-', true),
            data: [
                'amount' => $payment->amount,
                'payment_id' => $payment->id,
            ],
        );
    }

    public function verify(Payment $payment, array $payload): PaymentVerifyResult
    {
        $status = $payload['status'] ?? 'success';

        $paymentStatus = match ($status) {
            'success', 'accepted', 'completed' => PaymentStatusEnum::Accepted,
            'cancelled', 'canceled' => PaymentStatusEnum::Canceled,
            default => PaymentStatusEnum::Rejected,
        };

        return new PaymentVerifyResult(
            status: $paymentStatus,
            transactionId: $payload['payment_id'] ?? uniqid('test-', true),
            rawResponse: $payload,
        );
    }
}
