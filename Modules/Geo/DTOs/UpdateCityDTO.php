<?php

namespace Modules\Geo\DTOs;

final readonly class UpdateCityDTO
{
    /**
     * @param  array<string, array{title: string}>  $translations
     */
    public function __construct(
        public int $regionId,
        public array $translations,
    ) {}

    /**
     * @param  array{region_id: int, translations: array<string, array{title: string}>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            regionId: (int) $validated['region_id'],
            translations: $validated['translations'],
        );
    }
}
