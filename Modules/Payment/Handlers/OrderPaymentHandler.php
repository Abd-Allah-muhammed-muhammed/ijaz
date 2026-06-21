<?php

namespace Modules\Payment\Handlers;

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\OrderOffer;
use Modules\Payment\Contracts\PaymentHandlerInterface;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Services\WalletService;

class OrderPaymentHandler implements PaymentHandlerInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function onSuccess(Payment $payment): void
    {
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
    }

    public function onFailure(Payment $payment): void
    {
        // No domain changes needed on failure
    }

    public function productTypes(): array
    {
        return [OrderOffer::class];
    }
}
