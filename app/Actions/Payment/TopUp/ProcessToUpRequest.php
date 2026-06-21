<?php

namespace App\Actions\Payment\TopUp;

use App\Enums\OperationStatusEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Closure;
use Modules\Wallet\Models\TopUpRequest;

class ProcessToUpRequest
{
    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        /**
         * @var TopUpRequest $product
         */
        $product = $payment->product;
        assert($product->payment_method === PaymentMethodEnum::Online);
        $product->payment_status = PaymentStatusEnum::Accepted;
        $product->status = OperationStatusEnum::Approved;
        $product->transaction_id = $payment->transaction_id;
        $product->payment_driver = $payment->driver;

        $product->save();

        return $next($payment);
    }
}
