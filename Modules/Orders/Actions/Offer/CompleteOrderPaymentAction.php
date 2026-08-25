<?php

namespace Modules\Orders\Actions\Offer;

use Illuminate\Support\Facades\DB;
use Modules\Orders\DTOs\OrderFeesResult;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Services\WalletService;
use Throwable;

class CompleteOrderPaymentAction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Order $order,
        OrderOffer $offer,
        Payment $payment,
        OrderFeesResult $fees,
    ): void {
        DB::transaction(function () use ($order, $offer, $payment, $fees): void {
            $offer->update(['status' => OfferStatusEnum::Paid]);
            $order->update([
                'status' => OrderStatusEnum::InProgress,
                'price' => $fees->price,
                'user_fees' => $fees->userFees,
                'provider_fees' => $fees->providerFees,
            ]);

            $this->walletService->addPendingDebit(
                owner: $payment->user,
                amount: (float) $payment->amount,
                operation: $offer,
                description: "Order payment — OrderOffer#{$offer->id}",
            );

            $this->walletService->adjustPending(
                owner: $offer->provider,
                creditDelta: $fees->price,
                debitDelta: $fees->providerFees,
                operation: $offer,
                description: "Order payment received — OrderOffer#{$offer->id}",
            );
        });
    }
}
