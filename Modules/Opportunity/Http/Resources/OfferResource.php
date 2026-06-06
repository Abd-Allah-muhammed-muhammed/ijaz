<?php

namespace Modules\Opportunity\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Opportunity\Models\OpportunityOffer;

/** @mixin OpportunityOffer */
class OfferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'description' => $this->description,
            'status' => $this->status->toArray(),
            'author' => $this->whenLoaded('author', fn () => OpportunityAuthorResource::make($this->author)),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
