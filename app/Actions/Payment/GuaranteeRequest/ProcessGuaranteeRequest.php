<?php

namespace App\Actions\Payment\GuaranteeRequest;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\OrderOffer;
use App\Models\Payment;
use Closure;

class ProcessGuaranteeRequest
{
    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        /**
         * @var OrderOffer $product
         */
        $product = $payment->product;
        $product->status = GuaranteeRequestStatusEnum::InProgress;
        $product->save();

        return $next($payment);
    }
}
