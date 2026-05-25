<?php

namespace Modules\Classifieds\Http\Resources\Api;

use App\Http\Resources\Api\V1\CityResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Api\V1\RegionResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Api\CarBrandResource;
use Modules\Catalog\Http\Resources\Api\CarCategoryResource;
use Modules\Catalog\Http\Resources\Api\CarTypeResource;
use Modules\Classifieds\Models\CarAdvisement;

/** @mixin CarAdvisement */
class CarAdvisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'status' => $this->status ? [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ] : null,
            'operation' => $this->operation ? [
                'value' => $this->operation->value,
                'label' => $this->operation->label(),
                'color' => $this->operation->color(),
            ] : null,
            'usage_status' => $this->usage_status ? [
                'value' => $this->usage_status->value,
                'label' => $this->usage_status->label(),
                'color' => $this->usage_status->color(),
            ] : null,
            'year' => $this->year,
            'mileage' => $this->mileage,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'engine_size' => $this->engine_size,
            'color' => $this->color,
            'price' => $this->price,
            'show_price' => $this->show_price,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'car_brand_id' => $this->car_brand_id,
            'car_type_id' => $this->car_type_id,
            'car_category_id' => $this->car_category_id,
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,

            'car_brand' => new CarBrandResource($this->whenLoaded('carBrand')),
            'car_type' => new CarTypeResource($this->whenLoaded('carType')),
            'car_category' => new CarCategoryResource($this->whenLoaded('carCategory')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),

            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),

            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
