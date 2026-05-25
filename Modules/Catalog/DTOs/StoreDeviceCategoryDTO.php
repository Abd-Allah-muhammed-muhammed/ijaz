<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Support\Collection;
use Modules\Catalog\Http\Requests\Dashboard\DeviceCategoryRequest;

class StoreDeviceCategoryDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(DeviceCategoryRequest $request): self
    {
        return new self(
            translations: Collection::make($request->validated('translations'))
                ->map(fn ($attrs, $locale) => array_merge($attrs, ['locale' => $locale]))
                ->values()
                ->all(),
            icon: $request->file('icon')?->store('device_categories'),
            parentId: $request->validated('parent_id'),
        );
    }
}
