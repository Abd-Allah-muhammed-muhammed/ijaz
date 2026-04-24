<?php

namespace App\DTOs\CarCategory;

use Illuminate\Http\Request;

class StoreCarCategoryDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $icon,
        public readonly ?int $parentId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            translations: $request->validated('translations'),
            icon: $request->hasFile('icon') ? $request->file('icon')->store('car_categories') : null,
            parentId: $request->validated('parent_id'),
        );
    }
}
