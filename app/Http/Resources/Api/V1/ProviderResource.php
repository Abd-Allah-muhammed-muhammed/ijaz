<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Http\Resources\Api\V1\CategoryResource;
use Modules\Marketplace\Http\Resources\Api\V1\SkillResource;

/**
 * @see Provider
 *
 * @mixin Provider
 */
class ProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'name' => $this->name,
            'phone' => $this->phone,
            'image' => $this->logo_url,
            'email' => $this->email,
            'about' => $this->about,
            'address' => $this->address,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'rate' => $this->whenAggregated('reviews', 'avg', 'rate', $this->reviews_avg_rate),
            'reviews_count' => $this->whenCounted('reviews', $this->reviews_count),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
            'unread_notifications_count' => $this->whenCounted('unreadNotifications', $this->unread_notifications_count),
        ];
    }
}
