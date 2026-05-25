<?php

namespace Modules\Catalog\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Specialization;

/** @mixin Specialization */
class SpecializationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'parent_id' => $this->parent_id,
            'children_count' => $this->whenCounted('children', $this->children_count),
            'icon' => $this->icon ? Storage::url($this->icon) : null,
        ];
    }
}
