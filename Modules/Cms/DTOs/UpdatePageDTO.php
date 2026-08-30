<?php

namespace Modules\Cms\DTOs;

final readonly class UpdatePageDTO
{
    /**
     * @param  array<string, array{title: string, content?: string|null}>  $translations
     * @param  list<string>|null  $composedOfSlugs
     */
    public function __construct(
        public string $slug,
        public array $translations,
        public ?array $composedOfSlugs = null,
    ) {}

    /**
     * @param  array{slug: string, translations: array<string, array{title: string, content?: string|null}>, composed_of_slugs?: list<string>|null}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $composed = $validated['composed_of_slugs'] ?? null;

        return new self(
            slug: $validated['slug'],
            translations: $validated['translations'],
            composedOfSlugs: is_array($composed) && $composed !== [] ? array_values($composed) : null,
        );
    }
}
