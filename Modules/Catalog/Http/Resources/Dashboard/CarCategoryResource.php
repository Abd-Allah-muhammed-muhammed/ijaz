<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use Modules\Catalog\Models\CarCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CarCategory */
class CarCategoryResource extends JsonResource
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
            'value' => $this->id,
            'icon' => $this->icon_url,
            'children_count' => $this->whenCounted('children'),
            'has_children' => $this->whenExistsLoaded('children'),
            'parent_id' => $this->parent_id,
            'children' => CarCategoryResource::collection($this->whenLoaded('children')),
            'parent' => new CarCategoryResource($this->whenLoaded('parent')),
            $this->mergeWhen($this->relationLoaded('translation') || $this->relationLoaded('translations'), function () {
                return [
                    'title' => $this->title,
                ];
            }),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
