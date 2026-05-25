<?php

namespace Modules\Catalog\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\CarBrand;

/** @mixin CarBrand */
class CarBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image_url,
            'is_active' => $this->is_active,
        ];
    }
}
