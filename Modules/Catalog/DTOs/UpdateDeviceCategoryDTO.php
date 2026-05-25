<?php

namespace Modules\Catalog\DTOs;

use Modules\Catalog\Http\Requests\Dashboard\DeviceCategoryRequest;
use Modules\Catalog\Models\DeviceCategory;

class UpdateDeviceCategoryDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(DeviceCategoryRequest $request, DeviceCategory $deviceCategory): self
    {
        return new self(
            translations: $request->validated('translations'),
            icon: $request->hasFile('icon') ? $request->file('icon')->store('device_categories') : null,
            parentId: $request->validated('parent_id'),
        );
    }
}
