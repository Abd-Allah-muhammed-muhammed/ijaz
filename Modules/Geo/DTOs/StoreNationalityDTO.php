<?php

namespace Modules\Geo\DTOs;

final readonly class StoreNationalityDTO
{
    /**
     * @param  array<string, array{name: string}>  $translations
     */
    public function __construct(
        public array $translations,
    ) {}

    /**
     * @param  array{translations: array<string, array{name: string}>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(translations: $validated['translations']);
    }
}
