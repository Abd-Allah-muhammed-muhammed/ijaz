<?php

namespace Modules\Catalog\DTOs;

final readonly class StorePropertyTypeDTO
{
    /**
     * @param  array<string, array{name: string}>  $translations
     */
    public function __construct(
        public array $translations,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array{translations: array<string, array{name: string}>, is_active?: bool}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            translations: $validated['translations'],
            isActive: $validated['is_active'] ?? true,
        );
    }
}
