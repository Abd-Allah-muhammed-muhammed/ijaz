<?php

namespace Modules\Wallet\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Support\WalletTransactionDisplay;
use Modules\Wallet\Support\WalletTransactionStatusResolver;

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
            'reference_short' => WalletTransactionDisplay::operationReference($this->operation_id),
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'amount' => WalletTransactionDisplay::amount($credit, $debit, $pendingCredit, $pendingDebit),
            'is_pending' => WalletTransactionDisplay::isPendingOnly($credit, $debit, $pendingCredit, $pendingDebit),
            'is_credit' => ! WalletTransactionDisplay::isPendingOnly($credit, $debit, $pendingCredit, $pendingDebit) && $credit > 0,
            'credit' => number_format($credit, 2),
            'debit' => number_format($debit, 2),
            'balance_after' => number_format((float) $this->balance_after, 2),
            'description' => $this->description,
            'operation_id' => $this->operation_id,
            'operation_type' => trans(str($this->operation_type)->afterLast('\\')->value()),
            'pending_credit' => number_format($pendingCredit, 2),
            'pending_debit' => number_format($pendingDebit, 2),
            'wallet_id' => $this->wallet_id,
            'created_at' => $this->created_at,
            'transfer_status' => WalletTransactionStatusResolver::forTransaction($this->resource),
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
        ];
    }
}
