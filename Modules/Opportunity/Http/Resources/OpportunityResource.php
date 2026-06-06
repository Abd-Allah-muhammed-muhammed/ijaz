<?php

namespace Modules\Opportunity\Http\Resources;

use App\Http\Resources\Api\V1\CityResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Api\V1\RegionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Opportunity\Models\Opportunity;

/** @mixin Opportunity */
class OpportunityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'budget' => $this->budget,
            'status' => $this->status->toArray(),
            'author' => $this->whenLoaded('author', fn () => OpportunityAuthorResource::make($this->author)),
            'region' => RegionResource::make($this->whenLoaded('region')),
            'city' => CityResource::make($this->whenLoaded('city')),
            'accepted_offer' => OfferResource::make($this->whenLoaded('acceptedOffer')),
            'offers_count' => $this->whenCounted('offers'),
            'comments_count' => $this->whenCounted('comments'),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'phone' => $this->phone,
            'email' => $this->email,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
