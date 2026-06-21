<?php

namespace Modules\Guarantor\Actions\Payment;

use Closure;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class NotifyGuarantorPayment
{
    public function __invoke(Payment $payment, Closure $next): mixed
    {
        if ($payment->status->is(PaymentStatusEnum::Accepted)) {
            // TODO: add dedicated GuarantorPaymentReceivedNotification translation keys
            Log::info('Guarantor payment accepted', [
                'payment_id' => $payment->id,
                'product_type' => $payment->product_type,
                'product_id' => $payment->product_id,
            ]);
        }

        return $next($payment);
    }
}
