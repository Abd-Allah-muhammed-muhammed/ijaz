<?php

namespace Modules\Marketplace\DTOs;

use App\Enums\CategoryFeesTypeEnum;
use Illuminate\Http\UploadedFile;

final readonly class UpdateCategoryDTO
{
    /**
     * @param  array<string, array{title: string, description?: string|null}>  $translations
     */
    public function __construct(
        public ?int $parentId,
        public array $translations,
        public CategoryFeesTypeEnum $feesType,
        public ?float $fees,
        public ?UploadedFile $icon,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, ?UploadedFile $icon): self
    {
        $feesType = CategoryFeesTypeEnum::from($validated['fees_type']);

        return new self(
            parentId: isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            translations: $validated['translations'],
            feesType: $feesType,
            fees: isset($validated['fees']) ? (float) $validated['fees'] : null,
            icon: $icon,
        );
    }
}
