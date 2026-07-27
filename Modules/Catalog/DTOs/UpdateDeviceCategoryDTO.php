<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Catalog\Http\Requests\Dashboard\DeviceCategoryRequest;
use Modules\Catalog\Models\DeviceCategory;

class UpdateDeviceCategoryDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?UploadedFile $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(DeviceCategoryRequest $request, DeviceCategory $deviceCategory): self
    {
        return new self(
            translations: Collection::make($request->validated('translations'))
                ->map(fn ($attrs, $locale) => array_merge($attrs, ['locale' => $locale]))
                ->values()
                ->all(),
            icon: $request->file('icon'),
            parentId: $request->validated('parent_id'),
        );
    }
}
