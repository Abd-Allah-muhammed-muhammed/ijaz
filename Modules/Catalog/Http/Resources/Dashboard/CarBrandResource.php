<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\CarBrand;

/**
 * @mixin CarBrand
 */
class CarBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'image_url' => $this->image_url,
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [$translation->locale => ['name' => $translation->name]])),
        ];
    }
}
