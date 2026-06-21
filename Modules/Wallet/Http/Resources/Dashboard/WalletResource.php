<?php

namespace Modules\Wallet\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\Wallet;

/** @mixin Wallet */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'balance' => number_format($this->balance, 2),
            'pending_credit' => $this->pending_credit,
            'credit' => $this->credit,
            'pending_debit' => $this->pending_debit,
            'debit' => $this->debit,
            'total_earning' => $this->total_earning,
            'total_spent' => $this->total_spent,
            'transactions_count' => $this->whenCounted('transactions', $this->transactions_count),
            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
