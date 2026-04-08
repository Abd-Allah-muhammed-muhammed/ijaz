<?php

namespace App\Actions\Payment\GuaranteeRequest;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\GuaranteeRequest;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Wallet;
use Closure;
use RuntimeException;

class AddProviderTransactionForGuaranteeRequest
{
    public function __invoke(Payment $payment, Closure $next)
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }
        /**
         * @var Provider $provider
         * @var Wallet $wallet
         */
        $provider = null;
        $wallet = null;
        $debit = 0;
        $credit = 0;
        $balance_after = 0;
        $pending_credit = 0;
        $pending_debit = 0;
        $description = '';
        $balance_before = 0;
        switch ($payment->product_type) {
            case GuaranteeRequest::class :

                $provider = $payment->product->provider;
                $wallet = $provider->wallet()->lockForUpdate()->firstOrCreate();
                $pending_credit = $payment->amount;
                $pending_debit = $payment->product->fees ?? 0;
                $description = 'Payment received for '.$payment->product_type.' #'.$payment->product_id;
                $balance_before = $wallet->balance ?? 0;
                $balance_after = $wallet->balance ?? 0;
                $wallet->increment('pending_credit', $pending_credit);
                break;

            default:
                throw new RuntimeException('Unsupported product type: '.$payment->product_type);
        }
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
