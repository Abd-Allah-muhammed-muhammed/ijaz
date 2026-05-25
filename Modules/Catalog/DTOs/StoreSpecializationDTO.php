<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Support\Collection;
use Modules\Catalog\Http\Requests\Dashboard\SpecializationRequest;

class StoreSpecializationDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(SpecializationRequest $request): self
    {
        return new self(
            translations: Collection::make($request->validated('translations'))
                ->map(fn ($attrs, $locale) => array_merge($attrs, ['locale' => $locale]))
                ->values()
                ->all(),
            icon: $request->file('icon')?->store('specializations'),
            parentId: $request->validated('parent_id'),
        );
    }
}
