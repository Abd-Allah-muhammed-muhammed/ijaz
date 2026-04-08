<?php

namespace App\Http\Resources\Dashboard;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin City */
class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'region' => new RegionResource($this->whenLoaded('region')),
            $this->mergeWhen($this->whenLoaded('translation'), [
                'title' => $this->title,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
