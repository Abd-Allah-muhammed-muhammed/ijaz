<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\Request;

class UpdateCarTypeDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $image,
        public readonly bool $isActive,
        public readonly int $carBrandId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            translations: $request->validated('translations'),
            image: $request->hasFile('image') ? $request->file('image')->store('car_types', 'public') : null,
            isActive: $request->boolean('is_active', false),
            carBrandId: $request->validated('car_brand_id'),
        );
    }
}
