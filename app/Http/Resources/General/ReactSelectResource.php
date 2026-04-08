<?php

namespace App\Http\Resources\General;

use App\Contracts\Selects\IReactSelect;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IReactSelect */
class ReactSelectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
        ];
    }
}
