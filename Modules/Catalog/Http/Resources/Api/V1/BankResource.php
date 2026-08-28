<?php

namespace Modules\Catalog\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Catalog\Models\Bank;

/** @mixin Bank */
class BankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo_url,
            'is_active' => $this->is_active,
        ];
    }
}
