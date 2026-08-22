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
            'balance' => number_format((float) $this->balance, 2),
            'pending_credit' => number_format((float) $this->pending_credit, 2),
            'credit' => number_format((float) $this->credit, 2),
            'pending_debit' => number_format((float) $this->pending_debit, 2),
            'debit' => number_format((float) $this->debit, 2),
            'total_earning' => number_format((float) $this->total_earning, 2),
            'total_spent' => number_format((float) $this->total_spent, 2),
            'amount_in_transfer' => number_format((float) ($this->amount_in_transfer ?? 0), 2),
            'transactions_count' => $this->whenCounted('transactions', $this->transactions_count),
            'transactions' => WalletTransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
