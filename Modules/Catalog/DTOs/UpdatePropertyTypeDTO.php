<?php

namespace Modules\Catalog\DTOs;

final readonly class UpdatePropertyTypeDTO
{
    /**
     * @param  array<string, array{name: string}>  $translations
     */
    public function __construct(
        public array $translations,
        public ?bool $isActive = null,
    ) {}

    /**
     * @param  array{translations: array<string, array{name: string}>, is_active?: bool}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            translations: $validated['translations'],
            isActive: array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
        );
    }
}
