<?php

namespace Modules\Orders\Actions\Offer;

use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\DTOs\ValidateOrderPaymentAmountResult;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Models\Payment;

class ValidateOrderPaymentAmountAction
{
    public function __construct(
        private readonly CalculateOrderFeesAction $calculateOrderFees,
    ) {}

    public function handle(Order $order, OrderOffer $offer, Payment $payment): ValidateOrderPaymentAmountResult
    {
        $order->loadMissing('category');

        $fees = $this->calculateOrderFees->handle($order, (float) $offer->price);
        $expectedTotal = $fees->price + $fees->userFees;
        $paidAmount = (float) $payment->amount;
        $amountMatches = abs($expectedTotal - $paidAmount) < 0.01;
        $isPayableState = $offer->status->is(OfferStatusEnum::Accepted)
            && $order->status->is(OrderStatusEnum::OfferProvided)
            && $order->accepted_offer_id === $offer->id;
        $isValid = $isPayableState && $amountMatches;

        return new ValidateOrderPaymentAmountResult(
            isValid: $isValid,
            fees: $fees,
            expectedTotal: $expectedTotal,
            paidAmount: $paidAmount,
        );
    }
}
