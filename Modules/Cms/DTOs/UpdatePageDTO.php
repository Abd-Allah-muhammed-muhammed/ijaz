<?php

namespace Modules\Cms\DTOs;

final readonly class UpdatePageDTO
{
    /**
     * @param  array<string, array{title: string, content: string}>  $translations
     */
    public function __construct(
        public string $slug,
        public array $translations,
    ) {}

    /**
     * @param  array{slug: string, translations: array<string, array{title: string, content: string}>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            slug: $validated['slug'],
            translations: $validated['translations'],
        );
    }
}
