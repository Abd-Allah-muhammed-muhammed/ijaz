<?php

namespace App\Actions\Payment\Order;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\OrderOffer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Closure;
use RuntimeException;

class AddUserTransaction
{
    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        /**
         * @var User $user
         * @var Wallet $wallet
         */
        $user = null;
        $wallet = null;
        $debit = 0;
        $credit = 0;
        $balance_after = 0;
        $pending_credit = 0;
        $pending_debit = 0;
        $description = '';
        $balance_before = 0;
        switch ($payment->product_type) {
            case OrderOffer::class :

                $user = $payment->user;
                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();
                $pending_debit = $payment->amount;
                $description = 'Payment send for '.$payment->product_type.' #'.$payment->product_id;
                $balance_before = $wallet->balance;
                $balance_after = $wallet->balance;
                $wallet->increment('pending_debit', $pending_debit);
                break;

            default:
                throw new RuntimeException('Unsupported product type: '.$payment->product_type);
        }
        $user->walletTTransactions()->create([
            'wallet_id' => $wallet->id,
            'debit' => $debit,
            'credit' => $credit,
            'balance_after' => $balance_after,
            'balance_before' => $balance_before,
            'operation_type' => $payment->product_type,
            'operation_id' => $payment->product_id,
            'pending_credit' => $pending_credit,
            'description' => $description,
            'pending_debit' => $pending_debit,
        ]);

        return $next($payment);

    }
}
