<?php

namespace Modules\Marketplace\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Models\ProviderType;

/** @mixin ProviderType */
class ProviderTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'files' => collect($this->files)->mapWithKeys(fn ($v, $k) => [$k => (bool) $v]),
            'image' => $this->image_url,
            $this->mergeWhen($this->relationLoaded('translation') || $this->relationLoaded('translations'), function () {
                return [
                    'name' => $this->name,
                    'description' => $this->description,
                ];
            }),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
