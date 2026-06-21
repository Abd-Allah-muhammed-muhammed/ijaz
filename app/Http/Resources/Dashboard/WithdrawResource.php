<?php

namespace App\Http\Resources\Dashboard;

use Modules\Wallet\Models\TopUpRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TopUpRequest */
class WithdrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => OperationUserResource::make($this->whenLoaded('user')),
            'amount' => $this->amount,
            'status' => $this->status->toArray(),
            'admin_notes' => $this->admin_notes,
            'user_notes' => $this->user_notes,
            'created_at' => $this->created_at,
        ];
    }
}
