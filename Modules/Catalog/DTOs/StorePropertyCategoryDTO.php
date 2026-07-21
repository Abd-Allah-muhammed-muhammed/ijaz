<?php

namespace Modules\Catalog\DTOs;

final readonly class StorePropertyCategoryDTO
{
    /**
     * @param  array<string, array{title: string}>  $translations
     */
    public function __construct(
        public array $translations,
        public ?int $parentId = null,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array{translations?: array<string, array{title: string}>, parent_id?: int|null, is_active?: bool}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            translations: $validated['translations'] ?? [],
            parentId: $validated['parent_id'] ?? null,
            isActive: $validated['is_active'] ?? true,
        );
    }
}
