<?php

namespace Modules\Catalog\DTOs;

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
            translations: $request->validated('translations'),
            icon: $request->file('icon')?->store('device_categories'),
            parentId: $request->validated('parent_id'),
        );
    }
}
