<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\CarType;

/**
 * @mixin CarType
 */
class CarTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'image_url' => $this->image_url,
            'car_brand_id' => $this->car_brand_id,
            'brand' => new CarBrandResource($this->whenLoaded('carBrand')),
            'translations' => $this->whenLoaded('translations', fn () => $this->translations->mapWithKeys(fn ($translation) => [$translation->locale => ['name' => $translation->name]])),
        ];
    }
}
