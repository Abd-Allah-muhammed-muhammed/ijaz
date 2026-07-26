<?php

namespace Modules\Settings\DTOs;

final readonly class UpdateSettingsDTO
{
    /**
     * @param  array<string, string>  $values
     */
    public function __construct(
        public array $values,
    ) {}

    /**
     * @param  array{values: array<string, string>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array<string, string> $values */
        $values = $validated['values'];

        return new self(values: $values);
    }
}
