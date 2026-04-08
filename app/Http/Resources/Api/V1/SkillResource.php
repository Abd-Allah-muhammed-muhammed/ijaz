<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Skill */
class SkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            $this->mergeWhen($this->relationLoaded('translation') || $this->relationLoaded('translations'), [
                'title' => $this->title,
            ]),
            //      'translations' => $this->whenLoaded('translations', function () {
            //        return $this->translations->keyBy('locale');
            //      }),
        ];
    }
}
