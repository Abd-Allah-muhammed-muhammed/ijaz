<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UpdateCarTypeDTO
{
    public function __construct(
        public readonly array $translations,
        public readonly ?UploadedFile $image,
        public readonly bool $isActive,
        public readonly int $carBrandId,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            translations: Collection::make($request->validated('translations'))
                ->map(fn ($attrs, $locale) => array_merge($attrs, ['locale' => $locale]))
                ->values()
                ->all(),
            image: $request->file('image'),
            isActive: $request->boolean('is_active', false),
            carBrandId: $request->validated('car_brand_id'),
        );
    }
}
