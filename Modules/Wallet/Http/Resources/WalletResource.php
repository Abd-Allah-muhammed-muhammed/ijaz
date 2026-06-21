<?php

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\Wallet;

/**
 * @mixin Wallet
 */
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'balance' => number_format($this->balance, 2),
            'pending_credit' => (float) $this->pending_credit,
            'pending_debit' => (float) $this->pending_debit,
            'available' => (float) ($this->balance - $this->pending_debit),
            'total_earning' => (float) $this->total_earning,
            'total_spent' => (float) $this->total_spent,
        ];
    }
}
