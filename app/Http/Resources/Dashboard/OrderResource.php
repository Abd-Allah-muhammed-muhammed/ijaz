<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
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
            'price' => $this->price,
            'status' => $this->status->toArray(),
            'created_at' => $this->created_at,
            'histories_count' => $this->whenCounted('histories', $this->histories_count),
            'offers_count' => $this->whenCounted('offers', $this->offers_count),

            $this->mergeWhen(! $this->relationLoaded('user'), function () {
                return [
                    'user_id' => $this->user_id,
                ];
            }),
            $this->mergeWhen(! $this->relationLoaded('provider'), function () {
                return [
                    'provider_id' => $this->provider_id,
                ];
            }),
            $this->mergeWhen(! $this->relationLoaded('category'), function () {
                return [
                    'category_id' => $this->category_id,
                ];
            }),
            $this->mergeWhen(! $this->relationLoaded('acceptedOffer'), function () {
                return [
                    'accepted_offer_id' => $this->accepted_offer_id,
                ];
            }),
            'accepted_offer' => new OfferResource($this->whenLoaded('acceptedOffer')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'offers' => OfferResource::collection($this->whenLoaded('offers')),
            'provider' => new ProviderResource($this->whenLoaded('provider')),
            'user' => new UserResource($this->whenLoaded('user')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
