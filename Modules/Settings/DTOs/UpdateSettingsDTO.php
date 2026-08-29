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
     * @param  array{values: array<string, string|null>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array<string, string|null> $raw */
        $raw = $validated['values'];

        $values = [];
        foreach ($raw as $key => $content) {
            $values[$key] = (string) ($content ?? '');
        }

        return new self(values: $values);
    }
}
