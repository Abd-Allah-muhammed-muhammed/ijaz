<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Review */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'reviewer_type' => str($this->reviewer_type)->afterLast('\\'),
            'reviewer' => $this->whenLoaded('reviewer', fn ($reviewer) => [
                'name' => $reviewer->name,
                'image' => $reviewer->image_url,
                'socket_id' => $reviewer->getAuthIdentifierForBroadcasting(),
            ]),
            'reviewee_type' => str($this->reviewee_type)->afterLast('\\'),
            'reviewee' => $this->whenLoaded('reviewee', fn ($reviewee) => [
                'name' => $reviewee->name,
                'image' => $reviewee->image_url,
                'socket_id' => $reviewee->getAuthIdentifierForBroadcasting(),
            ]),
        ];
    }
}
