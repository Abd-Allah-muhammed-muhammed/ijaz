<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class StoreCarCategoryDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?UploadedFile $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(Request $request): self
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
