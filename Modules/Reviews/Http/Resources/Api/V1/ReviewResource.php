<?php

namespace Modules\Reviews\Http\Resources\Api\V1;

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
            // Closures are required: PHP evaluates array-args before mergeWhen runs, so an
            // eager ['reviewer' => $this->reviewer->…] always lazy-loads when unloaded.
            $this->mergeWhen(
                $this->relationLoaded('reviewer'),
                fn () => [
                    'reviewer' => [
                        'id' => $this->reviewer->id,
                        'name' => $this->reviewer->name,
                        'image' => $this->reviewer->image_url,
                        'socket_id' => $this->reviewer->getAuthIdentifierForBroadcasting(),
                    ],
                ],
            ),
            $this->mergeWhen(
                $this->relationLoaded('reviewee'),
                fn () => [
                    'reviewee' => [
                        'id' => $this->reviewee->id,
                        'name' => $this->reviewee->name,
                        'image' => $this->reviewee->image_url,
                        'socket_id' => $this->reviewee->getAuthIdentifierForBroadcasting(),
                    ],
                ],
            ),
            'operation_id' => $this->operation_id,
            'operation_type' => str($this->operation_type)->afterLast('\\')->toString(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,

        ];
    }
}
