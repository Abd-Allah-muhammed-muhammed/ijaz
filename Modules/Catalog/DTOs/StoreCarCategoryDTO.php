<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
            translations: $request->validated('translations'),
            icon: $request->file('icon'),
            parentId: $request->validated('parent_id'),
        );
    }
}
