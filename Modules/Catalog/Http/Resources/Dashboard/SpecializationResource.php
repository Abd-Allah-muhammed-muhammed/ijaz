<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

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
            'icon' => $this->icon ? Storage::url($this->icon) : null,
            'parent_id' => $this->parent_id,
            'children_count' => $this->whenCounted('children'),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [$translation->locale => ['title' => $translation->title]])),
            'parent' => new SpecializationResource($this->whenLoaded('parent')),
            'children' => SpecializationResource::collection($this->whenLoaded('children')),
        ];
    }
}
