<?php

namespace Modules\Orders\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\ProviderResource;
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
        ];
    }
}
