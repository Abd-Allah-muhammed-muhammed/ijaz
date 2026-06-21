<?php

namespace App\Actions\Payment;

use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Closure;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

class AddModelTransaction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        $user = $payment->user;
        $product = $payment->product;

        match ($payment->product_type) {
            TopUpRequest::class => $this->walletService->credit(
                $user,
                (float) $payment->amount,
                $product,
                'Wallet top-up approved',
            ),
            WithdrawRequest::class => $this->walletService->debit(
                $user,
                (float) $payment->amount,
                $product,
                'Withdraw request processed',
            ),
            default => null,
        };

        return $next($payment);
    }
}
