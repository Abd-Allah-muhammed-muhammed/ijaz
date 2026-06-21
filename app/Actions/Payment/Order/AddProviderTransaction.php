<?php

namespace App\Actions\Payment\Order;

use Modules\Payment\Enums\PaymentStatusEnum;
use App\Models\OrderOffer;
use Modules\Payment\Models\Payment;
use App\Models\User;
use Closure;
use Modules\Wallet\Services\WalletService;

class AddProviderTransaction
{
    /**
     * @param  Payment<User,OrderOffer>  $payment
     * @param  Closure(Payment): Payment  $next
     */
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function __invoke(Payment $payment, Closure $next): Payment
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        $offer = $payment->product;
        $provider = $offer->provider;

        $this->walletService->adjustPending(
            $provider,
            (float) $offer->order->price,
            (float) $offer->order->provider_fees,
            $payment->product,
            "Payment received for OrderOffer#{$payment->product_id}",
        );

        return $next($payment);
    }
}
