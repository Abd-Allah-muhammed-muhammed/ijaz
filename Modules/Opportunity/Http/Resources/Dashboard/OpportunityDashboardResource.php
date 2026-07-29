<?php

namespace Modules\Opportunity\Http\Resources\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;
use Modules\Opportunity\Models\Opportunity;

/** @mixin Opportunity */
class OpportunityDashboardResource extends JsonResource
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
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->getKey(),
                'name' => $this->author instanceof User
                    ? $this->author->name
                    : ($this->author->name ?? ''),
                'type' => $this->author instanceof User ? 'user' : 'provider',
            ]),
            'region' => RegionResource::make($this->whenLoaded('region')),
            'city' => CityResource::make($this->whenLoaded('city')),
            'offers' => $this->whenLoaded('offers', fn () => OfferDashboardResource::collection($this->offers)),
            'comments' => $this->whenLoaded('comments', fn () => CommentDashboardResource::collection($this->comments)),
            'accepted_offer' => $this->whenLoaded('acceptedOffer', fn () => $this->acceptedOffer
                ? new OfferDashboardResource($this->acceptedOffer)
                : null),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($media) => [
                'uuid' => $media->uuid,
                'url' => $media->getFullUrl(),
                'mime_type' => $media->mime_type,
            ])),
            'offers_count' => $this->whenCounted('offers'),
            'comments_count' => $this->whenCounted('comments'),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
