<?php

namespace Modules\Geo\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\MergesWhenTranslationLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Geo\Models\Region;

/** @mixin Region */
class RegionResource extends JsonResource
{
    use MergesWhenTranslationLoaded;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cities_count' => $this->whenCounted('cities'),
            $this->mergeWhenTranslationLoaded(fn () => [
                'title' => $this->title,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
