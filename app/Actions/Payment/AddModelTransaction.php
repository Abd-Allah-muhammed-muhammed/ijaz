<?php

namespace App\Actions\Payment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\OrderOffer;
use App\Models\Payment;
use App\Models\TopUpRequest;
use App\Models\Wallet;
use App\Models\WithdrawRequest;
use App\Traits\HasWallet;
use Closure;
use RuntimeException;

class AddModelTransaction
{
    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        /**
         * @var HasWallet $user
         * @var ?Wallet $wallet
         */
        $user = $payment->user;
        $wallet = new Wallet;
        $debit = 0;
        $credit = 0;
        $balance_after = 0;
        $pending_credit = 0;
        $pending_debit = 0;
        $description = '';
        $balance_before = 0;
        switch ($payment->product_type) {
            case OrderOffer::class :

                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();
                $pending_debit = $payment->amount;
                $description = 'Payment send for '.$payment->product_type.' #'.$payment->product_id;
                $balance_before = $wallet->balance;
                $balance_after = $wallet->balance;
                $wallet->increment('pending_debit', $pending_debit);
                break;

            case TopUpRequest::class :

                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();
                $credit = $payment->amount;
                $description = 'Wallet top-up for '.$payment->product_type.' #'.$payment->product_id;
                $balance_before = $wallet->balance;
                $balance_after = $wallet->balance + $credit;
                $wallet->increment('balance', $credit);
                break;

            case WithdrawRequest::class :

                $wallet = $user->wallet()->lockForUpdate()->firstOrCreate();
                $debit = $payment->amount;
                $description = 'Withdraw request payment for '.$payment->product_type.' #'.$payment->product_id;
                $balance_before = $wallet->balance;
                $balance_after = $wallet->balance - $debit;
                $wallet->decrement('balance', $debit);
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
