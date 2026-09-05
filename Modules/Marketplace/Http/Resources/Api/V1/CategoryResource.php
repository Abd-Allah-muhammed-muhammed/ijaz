<?php

namespace Modules\Marketplace\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\MergesWhenTranslationLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Models\Category;

/** @mixin Category */
class CategoryResource extends JsonResource
{
    use MergesWhenTranslationLoaded;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon_url,
            'children_count' => $this->whenCounted('children'),
            'parent_id' => $this->parent_id,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            $this->mergeWhenTranslationLoaded(fn () => [
                'title' => $this->title,
                'description' => $this->description,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
            'has_children' => $this->whenExistsLoaded('children', $this->children_exists),
        ];
    }
}
