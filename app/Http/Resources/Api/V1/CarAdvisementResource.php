<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CarAdvisement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'operation' => $this->operation?->value,
            'operation_label' => $this->operation?->label(),
            'usage_status' => $this->usage_status?->value,
            'usage_status_label' => $this->usage_status?->label(),
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

            $this->mergeWhen($this->relationLoaded('user'), function () {
                return [
                    'user' => $this->user ? [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'phone' => $this->user->phone,
                        'image' => $this->user->image_url,
                    ] : null,
                ];
            }),

            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
