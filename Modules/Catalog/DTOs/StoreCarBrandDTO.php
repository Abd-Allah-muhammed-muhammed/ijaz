<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class StoreCarBrandDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?UploadedFile $image,
        public readonly bool $isActive,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            translations: $request->validated('translations'),
            image: $request->file('image'),
            isActive: $request->boolean('is_active', false),
        );
    }
}
