<?php

namespace App\Http\Resources\Api\V1;

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
            $this->mergeWhen($this->relationLoaded('reviewer'), [
                'reviewer' => [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'image' => $this->reviewer->image_url,
                    'socket_id' => $this->reviewer->getAuthIdentifierForBroadcasting(),
                ],
            ]),
            $this->mergeWhen($this->relationLoaded('reviewee'), [
                'reviewee' => [
                    'id' => $this->reviewee->id,
                    'name' => $this->reviewee->name,
                    'image' => $this->reviewee->image_url,
                    'socket_id' => $this->reviewee->getAuthIdentifierForBroadcasting(),
                ],
            ]),
            'operation_id' => $this->operation_id,
            'operation_type' => str($this->operation_type)->afterLast('\\')->toString(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,

        ];
    }
}
