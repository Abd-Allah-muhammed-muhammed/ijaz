<?php

namespace App\Actions\Payment\Order;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Enums\Payment\PaymentStatusEnum;
use App\Models\OrderOffer;
use App\Models\Payment;
use Closure;

class ProcessOrder
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
        // Process the order here (e.g., update inventory, notify shipping, etc.)
        assert($product->order->acceptedOffer()->is($product));
        $product->status = OfferStatusEnum::Paid;
        $product->save();
        $product->order->status = OrderStatusEnum::InProgress;
        $product->order->price = $payment->amount;
        $product->order->save();

        return $next($payment);
    }
}
