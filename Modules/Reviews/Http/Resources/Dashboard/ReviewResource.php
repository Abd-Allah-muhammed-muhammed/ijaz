<?php

namespace Modules\Reviews\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Reviews\Models\Review;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reviewer_type' => str($this->reviewer_type)->afterLast('\\')->toString(),
            'reviewer' => $this->whenLoaded('reviewer', fn ($reviewer) => [
                'name' => $reviewer->name,
                'image' => $reviewer->image_url,
                'socket_id' => $reviewer->getAuthIdentifierForBroadcasting(),
            ]),
            'reviewee_type' => str($this->reviewee_type)->afterLast('\\')->toString(),
            'reviewee' => $this->whenLoaded('reviewee', fn ($reviewee) => [
                'name' => $reviewee->name,
                'image' => $reviewee->image_url,
                'socket_id' => $reviewee->getAuthIdentifierForBroadcasting(),
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
