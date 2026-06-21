<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WalletTransaction;

/** @mixin WalletTransaction */
class WalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'credit' => $this->credit,
            'debit' => $this->debit,
            'pending_debit' => $this->pending_debit,
            'pending_credit' => $this->pending_credit,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'operation_id' => $this->operation_id,
            'operation_type' => str($this->operation_type)->afterLast('\\'),
            'wallet_id' => $this->wallet_id,
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
        ];
    }
}
