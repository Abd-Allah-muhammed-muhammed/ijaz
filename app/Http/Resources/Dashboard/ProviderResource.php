<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Provider */
class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->padded_code,
            'iban' => $this->iban,
            'status' => $this->status->toArray(),
            'about' => $this->about,
            'logo' => $this->logo_url,
            'image' => $this->logo_url,
            'commercial_record' => $this->commercial_record_url,
            'provider_type_id' => $this->provider_type_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,

            'withdraw_requests_count' => $this->whenCounted('withdrawRequests', $this->withdraw_requests_count),
            'orders_count' => $this->whenCounted('orders', $this->orders_count),
            'provider_type' => ProviderTypeResource::make($this->whenLoaded('providerType')),
            'wallet' => WalletResource::make($this->whenLoaded('wallet')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'created_at' => $this->created_at,
            'reviews_count' => $this->whenCounted('reviews', $this->reviews_count),
            'average_rating' => $this->whenAggregated('reviews', 'rating', 'avg', function ($value) {
                return (float) number_format($value, 2);
            }, '0.0'),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'blocked_at' => $this->blocked_at?->toDateTimeString(),
            'blocked_until' => $this->blocked_until?->toDateTimeString(),
            'latest_block_history' => BlockHistoryResource::make($this->whenLoaded('latestBlockHistory')),
        ];
    }
}
