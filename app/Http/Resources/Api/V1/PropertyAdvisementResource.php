<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropertyAdvisement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyAdvisement */
class PropertyAdvisementResource extends JsonResource
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
            'price' => $this->price,
            'show_price' => $this->show_price,
            'area' => $this->area,
            'bedrooms_count' => $this->bedrooms_count,
            'bathrooms_count' => $this->bathrooms_count,
            'halls_count' => $this->halls_count,
            'age' => $this->age,
            'facade' => $this->facade,
            'street_width' => $this->street_width,
            'street_type' => $this->street_type,
            'phone' => $this->phone,
            'license' => $this->license,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'property_type_id' => $this->property_type_id,
            'city_id' => $this->city_id,
            'region_id' => $this->region_id,
            'category_id' => $this->category_id,

            'property_type' => new PropertyTypeResource($this->whenLoaded('propertyType')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'category' => new PropertyCategoryResource($this->whenLoaded('category')),

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
