<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PropertyType */
class PropertyTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            $this->mergeWhen($this->whenLoaded('translation'), [
                'name' => $this->name,
            ]),
        ];
    }
}
