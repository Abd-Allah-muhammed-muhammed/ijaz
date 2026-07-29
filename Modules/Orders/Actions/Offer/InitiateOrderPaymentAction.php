<?php

namespace Modules\Orders\Actions\Offer;

use App\Models\User;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class InitiateOrderPaymentAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, OrderOffer $offer, User $user): PaymentInitResult
    {
        if (
            $offer->status->isIn([OfferStatusEnum::Cancelled, OfferStatusEnum::Rejected, OfferStatusEnum::Paid]) ||
            $offer->order()->isNot($order) ||
            $order->user()->isNot($user)
        ) {
            throw new OrdersException('you can not pay for this order', Response::HTTP_BAD_REQUEST);
        }

        return $this->paymentService->initiate(
            owner: $user,
            product: $offer,
            amount: $order->user_total,
        );
    }
}
