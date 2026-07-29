<?php

namespace Modules\Orders\Http\Resources\Dashboard;

use App\Http\Resources\Dashboard\ProviderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Models\OrderOffer;

/** @mixin OrderOffer */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
            'price' => $this->price,
            'description' => $this->description,
            'status' => $this->status->toArray(),
            'created_at' => $this->created_at,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'provider_id' => $this->provider_id,
            'category_id' => $this->category_id,

        ];
    }
}
