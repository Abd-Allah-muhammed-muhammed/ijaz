<?php

namespace Modules\Wallet\Support;

use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

final class WalletTransactionStatusResolver
{
    /**
     * @return array{value: string, label: string, color: string}
     */
    public static function forTransaction(WalletTransaction $transaction): array
    {
        if ($transaction->relationLoaded('operation') && $transaction->operation !== null) {
            if ($transaction->operation instanceof WithdrawRequest) {
                return self::forWithdrawRequest($transaction->operation);
            }

            if ($transaction->operation instanceof TopUpRequest) {
                return $transaction->operation->status->toArray();
            }
        }

        return self::genericLedgerStatus(
            WalletTransactionDisplay::isPendingOnly(
                (float) $transaction->credit,
                (float) $transaction->debit,
                (float) $transaction->pending_credit,
                (float) $transaction->pending_debit,
            ),
        );
    }

    /**
     * @return array{value: string, label: string, color: string}
     */
    public static function forWithdrawRequest(WithdrawRequest $withdraw): array
    {
        if ($withdraw->relationLoaded('payoutRequest') && $withdraw->payoutRequest !== null) {
            return $withdraw->payoutRequest->status->toProviderStatus();
        }

        return $withdraw->status->toArray();
    }

    /**
     * @return array{value: string, label: string, color: string}
     */
    private static function genericLedgerStatus(bool $isPending): array
    {
        if ($isPending) {
            return [
                'value' => 'pending',
                'label' => trans('pending'),
                'color' => 'warning',
            ];
        }

        return [
            'value' => 'completed',
            'label' => trans('completed'),
            'color' => 'success',
        ];
    }
}
