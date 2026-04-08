<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'balance' => number_format($this->balance, 2),
            'pending_credit' => $this->pending_credit,
            'pending_debit' => $this->pending_debit,
            'total_earning' => $this->total_earning,
            'total_spent' => $this->total_spent,
            'transactions_count' => $this->whenCounted('transactions', $this->transactions_count),
            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
