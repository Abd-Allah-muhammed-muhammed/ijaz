<?php

namespace Modules\Geo\Http\Resources\Dashboard;

use App\Http\Resources\Concerns\MergesWhenTranslationLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Geo\Models\City;

/** @mixin City */
class CityResource extends JsonResource
{
    use MergesWhenTranslationLoaded;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'region' => new RegionResource($this->whenLoaded('region')),
            $this->mergeWhenTranslationLoaded(fn () => [
                'title' => $this->title,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
