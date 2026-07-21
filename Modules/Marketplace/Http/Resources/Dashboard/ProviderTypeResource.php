<?php

namespace Modules\Marketplace\Http\Resources\Dashboard;

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
            $this->mergeWhen($this->relationLoaded('translations') || $this->relationLoaded('translation'), function () {
                return [
                    'name' => $this->name,
                    'description' => $this->description,
                ];
            }),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
            'providers_count' => $this->whenLoaded('providers_count', $this->providers_count),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
