<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Actions\Offer\CompleteOrderPaymentAction;
use Modules\Orders\Actions\Offer\RejectOrderPaymentAmountMismatchAction;
use Modules\Orders\Actions\Offer\ValidateOrderPaymentAmountAction;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Events\PaymentCompleted;
use Throwable;

class HandleOrderPaymentCompleted
{
    public function __construct(
        private readonly ValidateOrderPaymentAmountAction $validateOrderPaymentAmount,
        private readonly CompleteOrderPaymentAction $completeOrderPayment,
        private readonly RejectOrderPaymentAmountMismatchAction $rejectOrderPaymentAmountMismatch,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        $offer = $payment->product;
        $order = $offer->order;

        $validation = $this->validateOrderPaymentAmount->handle($order, $offer, $payment);

        if (! $validation->isValid || $validation->fees === null) {
            $this->rejectOrderPaymentAmountMismatch->handle($payment, $order, $offer, $validation);

            return;
        }

        $this->completeOrderPayment->handle($order, $offer, $payment, $validation->fees);
    }
}
