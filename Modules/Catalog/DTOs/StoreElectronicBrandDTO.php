<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Support\Collection;
use Modules\Catalog\Http\Requests\Dashboard\ElectronicBrandRequest;

class StoreElectronicBrandDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $image,
        public readonly bool $isActive,
    ) {}

    public static function fromRequest(ElectronicBrandRequest $request): self
    {
        return new self(
            translations: Collection::make($request->validated('translations'))
                ->map(fn ($attrs, $locale) => array_merge($attrs, ['locale' => $locale]))
                ->values()
                ->all(),
            image: $request->file('image')?->store('electronic_brands', 'public'),
            isActive: $request->boolean('is_active', true),
        );
    }
}
