<?php

namespace Modules\Classifieds\Http\Resources\Api;

use App\Http\Resources\Api\V1\CityResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Api\V1\RegionResource;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Api\DeviceCategoryResource;
use Modules\Classifieds\Models\ElectronicAdvisement;

/** @mixin ElectronicAdvisement */
class ElectronicAdvisementResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'device_category_id' => $this->device_category_id,
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,

            'device_category' => new DeviceCategoryResource($this->whenLoaded('deviceCategory')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),

            'user' => $this->whenLoaded('user', fn () => UserResource::make($this->user)),

            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
