<?php

namespace Modules\Geo\DTOs;

final readonly class StoreRegionDTO
{
    /**
     * @param  array<string, array{title: string}>  $translations
     */
    public function __construct(
        public array $translations,
    ) {}

    /**
     * @param  array{translations: array<string, array{title: string}>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(translations: $validated['translations']);
    }
}
