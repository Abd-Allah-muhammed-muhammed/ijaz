<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Provider;
use App\Models\TopUpRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TopUpRequest */
class WithdrawRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => match (get_class($this->user)) {
                User::class => UserResource::make($this->user),
                Provider::class => ProviderResource::make($this->user),
                default => null,
            }),
            'amount' => $this->amount,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'user_notes' => $this->user_notes,
            'created_at' => $this->created_at,
        ];
    }
}
