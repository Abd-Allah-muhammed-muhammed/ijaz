<?php

namespace Modules\Wallet\Listeners;

use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Models\TopUpRequest;

class HandleTopUpPaymentFailed
{
    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== TopUpRequest::class) {
            return;
        }

        $payment->product->update([
            'payment_status' => PaymentStatusEnum::Rejected,
        ]);
    }
}
