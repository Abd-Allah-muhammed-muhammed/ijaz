<?php

namespace Modules\Orders\Actions\Offer;

use Illuminate\Support\Facades\Log;
use Modules\Orders\DTOs\ValidateOrderPaymentAmountResult;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class RejectOrderPaymentAmountMismatchAction
{
    public function handle(
        Payment $payment,
        Order $order,
        OrderOffer $offer,
        ValidateOrderPaymentAmountResult $validation,
    ): void {
        $payment->update([
            'status' => PaymentStatusEnum::NeedsReview,
            'message' => sprintf(
                'Order payment amount mismatch: paid %.2f, expected %.2f (offer price %.2f + user fees %.2f)',
                $validation->paidAmount,
                $validation->expectedTotal,
                $validation->fees?->price ?? 0.0,
                $validation->fees?->userFees ?? 0.0,
            ),
        ]);

        Log::warning('Order payment amount mismatch — payment flagged for admin review', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'order_offer_id' => $offer->id,
            'paid_amount' => $validation->paidAmount,
            'expected_total' => $validation->expectedTotal,
            'offer_price' => (float) $offer->price,
        ]);
    }
}
