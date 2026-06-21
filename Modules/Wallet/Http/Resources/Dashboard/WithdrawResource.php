<?php

namespace Modules\Wallet\Http\Resources\Dashboard;

use App\Http\Resources\Dashboard\OperationUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Wallet\Models\WithdrawRequest;

/** @mixin WithdrawRequest */
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
