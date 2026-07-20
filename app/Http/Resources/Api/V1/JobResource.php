<?php

namespace App\Http\Resources\Api\V1;

use App\Models\JobOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Geo\Http\Resources\Dashboard\CityResource;

/** @mixin JobOffer */
class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'expected_salary' => $this->expected_salary,
            'expired_at' => $this->expired_at,
            'contact_number' => $this->contact_number,
            'created_at' => $this->created_at,
            $this->mergeWhen($this->relationLoaded('user'), function () {
                return [
                    'user' => [
                        'id' => $this->user->id,
                        'socket_id' => $this->user->getAuthIdentifierForBroadcasting(),
                        'name' => $this->user->name,
                        'phone' => $this->user->phone,
                        'image' => $this->user->image_url,
                    ],
                ];
            }),

            'city_id' => $this->city_id,
            'region_id' => $this->region_id,
            'nationality_id' => $this->nationality_id,

            'city' => new CityResource($this->whenLoaded('city')),
            'nationality' => new NationalityResource($this->whenLoaded('nationality')),
            'region' => new RegionResource($this->whenLoaded('region')),
            'type' => $this->type->label(),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
