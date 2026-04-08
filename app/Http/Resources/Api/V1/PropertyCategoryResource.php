<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropertiyCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertiyCategory */
class PropertyCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            $this->mergeWhen($this->whenLoaded('translation'), [
                'title' => $this->title,
            ]),
        ];
    }
}
