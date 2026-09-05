<?php

namespace Modules\Geo\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\MergesWhenTranslationLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Geo\Models\Nationality;

/** @mixin Nationality */
class NationalityResource extends JsonResource
{
    use MergesWhenTranslationLoaded;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            $this->mergeWhenTranslationLoaded(fn () => [
                'name' => $this->name,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
