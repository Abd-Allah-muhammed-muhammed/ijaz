<?php

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Support\WalletTransactionDescription;

/**
 * @mixin WalletTransaction
 */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Single display amount for mobile: verified against live rows —
            // top-up credit, withdraw pending hold, reverse hold, and final debit.
            'amount' => (float) max(
                abs((float) $this->credit),
                abs((float) $this->debit),
                abs((float) $this->pending_credit),
                abs((float) $this->pending_debit),
            ),
            'credit' => (float) $this->credit,
            'debit' => (float) $this->debit,
            'pending_credit' => (float) $this->pending_credit,
            'pending_debit' => (float) $this->pending_debit,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => WalletTransactionDescription::for($this->resource),
            'operation_type' => $this->operation_type,
            'operation_id' => $this->operation_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
