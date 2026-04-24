<?php

namespace App\DTOs\CarBrand;

use Illuminate\Http\Request;

class StoreCarBrandDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?string $image,
        public readonly bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            translations: $request->validated('translations'),
            image: $request->hasFile('image') ? $request->file('image')->store('car_brands', 'public') : null,
            isActive: $request->boolean('is_active', false),
        );
    }
}
