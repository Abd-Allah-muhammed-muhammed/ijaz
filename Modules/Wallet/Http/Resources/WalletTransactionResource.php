<?php

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Support\WalletTransactionDisplay;
use Modules\Wallet\Support\WalletTransactionStatusResolver;

/**
 * @mixin WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => WalletTransactionDisplay::amount(
                (float) $this->credit,
                (float) $this->debit,
                (float) $this->pending_credit,
                (float) $this->pending_debit,
            ),
            'credit' => (float) $this->credit,
            'debit' => (float) $this->debit,
            'pending_credit' => (float) $this->pending_credit,
            'pending_debit' => (float) $this->pending_debit,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'operation_type' => trans(str($this->operation_type)->afterLast('\\')->value()),
            'operation_id' => $this->operation_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'transfer_status' => WalletTransactionStatusResolver::forTransaction($this->resource),
        ];
    }
}
