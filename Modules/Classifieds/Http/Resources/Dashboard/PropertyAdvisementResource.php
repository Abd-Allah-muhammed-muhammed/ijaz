<?php

namespace Modules\Classifieds\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\PropertyCategoryResource;
use App\Http\Resources\Dashboard\PropertyTypeResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Classifieds\Models\PropertyAdvisement;

/** @mixin PropertyAdvisement */
class PropertyAdvisementResource extends JsonResource
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
            'facade' => $this->facade,
            'street_width' => $this->street_width,
            'street_type' => $this->street_type,
            'age' => $this->age,
            'area' => $this->area,
            'price' => $this->price,
            'show_price' => $this->show_price,
            'bedrooms_count' => $this->bedrooms_count,
            'bathrooms_count' => $this->bathrooms_count,
            'halls_count' => $this->halls_count,
            'phone' => $this->phone,
            'license' => $this->license,
            'options' => $this->options,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'address' => $this->address,
            'created_at' => $this->created_at,

            $this->mergeWhen(! $this->relationLoaded('user'), fn () => ['user_id' => $this->user_id]),
            $this->mergeWhen(! $this->relationLoaded('propertyType'), fn () => ['property_type_id' => $this->property_type_id]),
            $this->mergeWhen(! $this->relationLoaded('city'), fn () => ['city_id' => $this->city_id]),
            $this->mergeWhen(! $this->relationLoaded('region'), fn () => ['region_id' => $this->region_id]),
            $this->mergeWhen(! $this->relationLoaded('category'), fn () => ['category_id' => $this->category_id]),

            'property_type' => new PropertyTypeResource($this->whenLoaded('propertyType')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'category' => new PropertyCategoryResource($this->whenLoaded('category')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
