<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Nationality */
class NationalityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            $this->mergeWhen($this->whenLoaded('translation'), [
                'name' => $this->name,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
