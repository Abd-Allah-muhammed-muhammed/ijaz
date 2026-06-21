<?php

namespace App\Http\Resources\Dashboard;

use Modules\Wallet\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
