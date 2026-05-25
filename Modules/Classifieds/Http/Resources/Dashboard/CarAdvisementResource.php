<?php

namespace Modules\Classifieds\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Dashboard\CarBrandResource;
use Modules\Catalog\Http\Resources\Dashboard\CarCategoryResource;
use Modules\Catalog\Http\Resources\Dashboard\CarTypeResource;
use Modules\Classifieds\Models\CarAdvisement;

/** @mixin CarAdvisement */
class CarAdvisementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image_url,
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
            'options' => $this->options,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'created_at' => $this->created_at,

            $this->mergeWhen(! $this->relationLoaded('user'), fn () => ['user_id' => $this->user_id]),
            $this->mergeWhen(! $this->relationLoaded('carBrand'), fn () => ['car_brand_id' => $this->car_brand_id]),
            $this->mergeWhen(! $this->relationLoaded('carType'), fn () => ['car_type_id' => $this->car_type_id]),
            $this->mergeWhen(! $this->relationLoaded('carCategory'), fn () => ['car_category_id' => $this->car_category_id]),
            $this->mergeWhen(! $this->relationLoaded('city'), fn () => ['city_id' => $this->city_id]),
            $this->mergeWhen(! $this->relationLoaded('region'), fn () => ['region_id' => $this->region_id]),

            'car_brand' => new CarBrandResource($this->whenLoaded('carBrand')),
            'car_type' => new CarTypeResource($this->whenLoaded('carType')),
            'car_category' => new CarCategoryResource($this->whenLoaded('carCategory')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
