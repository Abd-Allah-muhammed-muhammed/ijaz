<?php

namespace Modules\Classifieds\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\MediaResource;
use App\Http\Resources\Dashboard\CityResource;
use App\Http\Resources\Dashboard\RegionResource;
use App\Http\Resources\Dashboard\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Http\Resources\Dashboard\SpecializationResource;
use Modules\Classifieds\Models\InstituteAdvisement;

/** @mixin InstituteAdvisement */
class InstituteAdvisementResource extends JsonResource
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
            'type' => $this->type ? [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'color' => $this->type->color(),
            ] : null,
            'study_type' => $this->study_type ? [
                'value' => $this->study_type->value,
                'label' => $this->study_type->label(),
                'color' => $this->study_type->color(),
            ] : null,
            'study_level' => $this->study_level ? [
                'value' => $this->study_level->value,
                'label' => $this->study_level->label(),
                'color' => $this->study_level->color(),
            ] : null,
            'price' => $this->price,
            'discounted_price' => $this->discounted_price,
            'days_count' => $this->days_count,
            'hours_count' => $this->hours_count,
            'goals' => $this->goals,
            'payment_notes' => $this->payment_notes,
            'phone' => $this->phone,
            'website' => $this->website,
            'registration_url' => $this->registration_url,
            'course_url' => $this->course_url,
            'quality_url' => $this->quality_url,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'registration_start' => $this->registration_start,
            'registration_end' => $this->registration_end,
            'study_start' => $this->study_start,
            'study_end' => $this->study_end,
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            $this->mergeWhen(! $this->relationLoaded('user'), fn () => ['user_id' => $this->user_id]),
            $this->mergeWhen(! $this->relationLoaded('specialization'), fn () => ['specialization_id' => $this->specialization_id]),
            $this->mergeWhen(! $this->relationLoaded('city'), fn () => ['city_id' => $this->city_id]),
            $this->mergeWhen(! $this->relationLoaded('region'), fn () => ['region_id' => $this->region_id]),

            'specialization' => new SpecializationResource($this->whenLoaded('specialization')),
            'city' => new CityResource($this->whenLoaded('city')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'user' => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
