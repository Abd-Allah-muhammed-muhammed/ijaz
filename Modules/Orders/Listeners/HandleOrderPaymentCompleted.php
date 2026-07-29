<?php

namespace Modules\Orders\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Wallet\Services\WalletService;

class HandleOrderPaymentCompleted
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== OrderOffer::class) {
            return;
        }

        DB::transaction(function () use ($payment) {
            $offer = $payment->product;
            $order = $offer->order;

            $offer->update(['status' => OfferStatusEnum::Paid]);
            $order->update([
                'status' => OrderStatusEnum::InProgress,
                'price' => $payment->amount,
            ]);

            $this->walletService->addPendingDebit(
                owner: $payment->user,
                amount: $payment->amount,
                operation: $offer,
                description: "Order payment — OrderOffer#{$offer->id}",
            );

            $this->walletService->adjustPending(
                owner: $offer->provider,
                creditDelta: $order->price,
                debitDelta: $order->provider_fees,
                operation: $offer,
                description: "Order payment received — OrderOffer#{$offer->id}",
            );
        });
    }
}
