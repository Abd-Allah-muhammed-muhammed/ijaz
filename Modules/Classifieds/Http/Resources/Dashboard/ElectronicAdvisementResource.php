<?php

namespace Modules\Classifieds\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Dashboard\DeviceCategoryResource;
use Modules\Catalog\Http\Resources\Dashboard\ElectronicBrandResource;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Geo\Http\Resources\Dashboard\CityResource;
use Modules\Geo\Http\Resources\Dashboard\RegionResource;

/** @mixin ElectronicAdvisement */
class ElectronicAdvisementResource extends JsonResource
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
            'image_url' => $this->image_url,
            'status' => $this->status ? [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ] : null,
            'condition' => $this->condition ? [
                'value' => $this->condition->value,
                'label' => $this->condition->label(),
                'color' => $this->condition->color(),
            ] : null,
            'color' => $this->color,
            'price' => $this->price,
            'show_price' => $this->show_price,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
            'model_name' => $this->model_name,
            'storage' => $this->storage,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            $this->mergeWhen(! $this->relationLoaded('user'), fn () => ['user_id' => $this->user_id]),
            $this->mergeWhen(! $this->relationLoaded('deviceCategory'), fn () => ['device_category_id' => $this->device_category_id]),
            $this->mergeWhen(! $this->relationLoaded('electronicBrand'), fn () => ['electronic_brand_id' => $this->electronic_brand_id]),
            $this->mergeWhen(! $this->relationLoaded('city'), fn () => ['city_id' => $this->city_id]),
            $this->mergeWhen(! $this->relationLoaded('region'), fn () => ['region_id' => $this->region_id]),

            'device_category' => new DeviceCategoryResource($this->whenLoaded('deviceCategory')),
            'electronic_brand' => new ElectronicBrandResource($this->whenLoaded('electronicBrand')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
