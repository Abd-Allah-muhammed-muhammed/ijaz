<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

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
            'is_active' => $this->is_active,
            'logo' => $this->logo_url,
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
