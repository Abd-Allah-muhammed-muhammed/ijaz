<?php

namespace Modules\Wallet\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Support\WalletTransactionDisplay;

/** @mixin WalletTransaction */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $credit = (float) $this->credit;
        $debit = (float) $this->debit;
        $pendingCredit = (float) $this->pending_credit;
        $pendingDebit = (float) $this->pending_debit;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'amount' => WalletTransactionDisplay::amount($credit, $debit, $pendingCredit, $pendingDebit),
            'is_pending' => WalletTransactionDisplay::isPendingOnly($credit, $debit, $pendingCredit, $pendingDebit),
            'credit' => $this->credit,
            'debit' => $this->debit,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'operation_id' => $this->operation_id,
            'operation_type' => trans(str($this->operation_type)->afterLast('\\')->value()),
            'pending_credit' => $this->pending_credit,
            'pending_debit' => $this->pending_debit,
            'wallet_id' => $this->wallet_id,
            'created_at' => $this->created_at,
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
        ];
    }
}
