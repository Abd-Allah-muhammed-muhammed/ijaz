<?php

namespace Modules\Payment\Gateways;

use Modules\Payment\Actions\HandleRajhiCallbackAction;
use Modules\Payment\Actions\InitiateRajhiPaymentAction;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Models\Payment;

class RajhiGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly InitiateRajhiPaymentAction $initiateAction,
        private readonly HandleRajhiCallbackAction $callbackAction,
    ) {}

    public function getConfig(): array
    {
        $mode = config('payment.drivers.rajhi.mode', 'test');

        return config("payment.drivers.rajhi.{$mode}", []);
    }

    public function initiate(Payment $payment): PaymentInitResult
    {
        return $this->initiateAction->handle($payment);
    }

    public function verify(Payment $payment, array $payload): PaymentVerifyResult
    {
        return $this->callbackAction->handle($payment, $payload);
    }
}
