<?php

namespace Modules\Marketplace\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Models\Category;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->id,
            'icon' => $this->icon_url,
            'children_count' => $this->whenCounted('children'),
            'has_children' => $this->whenExistsLoaded('children'),
            'parent_id' => $this->parent_id,
            'children' => $this->when($this->relationLoaded('children') || $this->relationLoaded('childrenRecursive'), function () {
                if ($this->relationLoaded('childrenRecursive')) {
                    $v = $this->childrenRecursive;
                } else {
                    $v = $this->children;
                }

                return CategoryResource::collection($v);
            }),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            $this->mergeWhen($this->relationLoaded('translation') || $this->relationLoaded('translations'), function () {

                return [
                    'title' => $this->title,
                    'description' => $this->description,
                ];
            }),
            $this->mergewhen($this->relationLoaded('children') || $this->relationLoaded('childrenRecursive'), function () {
                if ($this->relationLoaded('childrenRecursive')) {
                    return [
                        'disabled' => $this->childrenRecursive->isNotEmpty(),
                    ];
                } else {
                    return [
                        'disabled' => $this->children->isNotEmpty(),
                    ];
                }
            }),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'provider_skills' => SkillResource::collection($this->whenLoaded('providerSkills')),
            'fees' => $this->fees,
            'fees_type' => $this->fees_type->toArray(),
        ];
    }
}
