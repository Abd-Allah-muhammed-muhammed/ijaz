<?php

namespace App\Http\Resources\Api\V1\PropertyType;

use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyType */
class PropertyTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'translations' => $this->whenLoaded('translations', fn() => $this->translations->mapWithKeys(fn($translation) => [$translation->locale => ['name' => $translation->name]])),
        ];
    }
}
