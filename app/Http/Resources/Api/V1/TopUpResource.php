<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TopUpRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TopUpRequest */
class TopUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => GuaranteeRequestUserResource::make($this->whenLoaded('user')),
            'amount' => $this->amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'admin_notes' => $this->admin_notes,
            'transaction_image' => $this->transaction_image,
            'user_notes' => $this->user_notes,
            'created_at' => $this->created_at,
        ];
    }
}
