<?php

namespace App\Actions\Payment\Order;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment;
use Closure;
use Modules\Wallet\Services\WalletService;

class AddUserTransaction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        $this->walletService->addPendingDebit(
            $payment->user,
            (float) $payment->amount,
            $payment->product,
            "Payment for OrderOffer#{$payment->product_id}",
        );

        return $next($payment);
    }
}
