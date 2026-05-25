<?php

namespace Modules\Catalog\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\PropertiyCategory;

/** @mixin PropertiyCategory */
class PropertyCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'is_active' => $this->is_active,
            'parent_id' => $this->parent_id,
            'children_count' => $this->whenCounted('children'),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [$translation->locale => ['title' => $translation->title]])),
            'parent' => new PropertyCategoryResource($this->whenLoaded('parent')),
            'children' => PropertyCategoryResource::collection($this->whenLoaded('children')),
        ];
    }
}
