<?php

namespace Modules\Catalog\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\DeviceCategory;

/** @mixin DeviceCategory */
class DeviceCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'icon' => $this->icon ? Storage::url($this->icon) : null,
            'parent_id' => $this->parent_id,
            'children_count' => $this->whenCounted('children', $this->children_count),
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
