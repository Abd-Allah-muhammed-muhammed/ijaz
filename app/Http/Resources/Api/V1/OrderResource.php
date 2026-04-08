<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'expected_time' => $this->expected_time,
            'budget_start' => $this->budget_start,
            'budget_end' => $this->budget_end,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'price' => $this->price,
            'status' => $this->status,
            'provider' => ProviderResource::make($this->whenLoaded('provider')),
            'user' => UserResource::make($this->whenLoaded('user')),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'offers_count' => $this->whenCounted('offers', $this->offers_count),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
            'media_count' => $this->whenCounted('media', $this->media_count),
            'accepted_offer' => OfferResource::make($this->whenLoaded('acceptedOffer')),
            'histories' => $this->whenLoaded('histories'),
            'user_id' => $this->when(! $this->relationLoaded('user'), $this->user_id),
            'provider_id' => $this->when(! $this->relationLoaded('provider'), $this->provider_id),
            'category_id' => $this->when(! $this->relationLoaded('category'), $this->category_id),
            'accepted_offer_id' => $this->when(! $this->relationLoaded('acceptedOffer'), $this->accepted_offer_id),
            'histories_count' => $this->whenCounted('histories', $this->histories_count),
            'city' => CityResource::make($this->whenLoaded('city')),
            'region' => RegionResource::make($this->whenLoaded('region')),
            'city_id' => $this->when(! $this->relationLoaded('city'), $this->city_id),
            'region_id' => $this->when(! $this->relationLoaded('region'), $this->region_id),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'skills_count' => $this->whenCounted('skills', $this->skills_count),
            'user_total' => $this->user_total,
            'provider_total' => $this->provider_total,
            'user_fees' => $this->user_fees,
            'provider_fees' => $this->provider_fees,
        ];
    }
}
