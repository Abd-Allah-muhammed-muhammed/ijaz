<?php

namespace App\Actions\Payment\Order;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\OrderOffer;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Closure;
use DB;
use RuntimeException;

class AddProviderTransaction
{
    /**
     * Handle the action.
     *
     * @param  Payment<User,OrderOffer>  $payment
     * @param  Closure(Payment): Payment  $next
     *
     * @throws RuntimeException
     */
    public function __invoke(Payment $payment, Closure $next): Payment
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        $debit = 0;
        $credit = 0;
        $balance_after = 0;
        $pending_credit = 0;
        $pending_debit = 0;
        $description = '';
        $balance_before = 0;

        $offer = $payment->product;
        $provider = $offer->provider;
        /**
         * @var Wallet $wallet
         */
        $wallet = $provider->wallet()->lockForUpdate()->firstOrCreate();
        $pending_credit = $offer->order->price;
        $pending_debit = $offer->order->provider_fees;
        $description = 'Payment received for '.$payment->product_type.' #'.$payment->product_id;
        $balance_before = $wallet->balance;
        $balance_after = $wallet->balance;
        $wallet->increment('pending_credit', $pending_credit, extra: [
            'pending_debit' => DB::raw('pending_debit - '.$pending_debit),
        ]);

        $provider->walletTTransactions()->create([
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
