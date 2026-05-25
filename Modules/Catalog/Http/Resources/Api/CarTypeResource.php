<?php

namespace Modules\Catalog\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\CarType;

/** @mixin CarType */
class CarTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'car_brand_id' => $this->car_brand_id,
            'car_brand' => $this->whenLoaded('carBrand', fn () => [
                'id' => $this->carBrand->id,
                'name' => $this->carBrand->name,
            ]),
            'image' => $this->image_url,
            'is_active' => $this->is_active,
        ];
    }
}
