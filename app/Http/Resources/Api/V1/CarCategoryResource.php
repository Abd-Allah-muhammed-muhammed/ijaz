<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CarCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CarCategory */
class CarCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'children_count' => $this->whenCounted('children', $this->children_count),
            'icon' => $this->icon_url,
        ];
    }
}
