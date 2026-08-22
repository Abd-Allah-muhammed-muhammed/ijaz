<?php

namespace Modules\Wallet\Support;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Exceptions\MissingTransferStatusEagerLoadException;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

final class WalletTransactionTransferStatusResolver
{
    /**
     * @return array{value: string, label: string, color: string}|null
     */
    public function resolve(WalletTransaction $transaction): ?array
    {
        if ($transaction->operation_type !== WithdrawRequest::class) {
            return null;
        }

        if (! $transaction->relationLoaded('operation')) {
            return $this->handleMissingEagerLoad(
                $transaction,
                'transfer_status requires operation to be eager-loaded on withdraw wallet transactions',
            );
        }

        $operation = $transaction->operation;

        if (! $operation instanceof WithdrawRequest) {
            return null;
        }

        if (! $operation->relationLoaded('payoutRequest')) {
            return $this->handleMissingEagerLoad(
                $transaction,
                'transfer_status requires operation.payoutRequest to be eager-loaded on withdraw wallet transactions',
            );
        }

        return $operation->payoutRequest?->status->toProviderStatus();
    }

    /**
     * @return null
     */
    private function handleMissingEagerLoad(WalletTransaction $transaction, string $message): ?array
    {
        if (app()->isProduction()) {
            Log::warning($message, [
                'wallet_transaction_id' => $transaction->id,
                'operation_id' => $transaction->operation_id,
            ]);

            return null;
        }

        throw new MissingTransferStatusEagerLoadException($message);
    }
}
