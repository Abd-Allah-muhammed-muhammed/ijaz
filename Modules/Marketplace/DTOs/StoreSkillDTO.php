<?php

namespace Modules\Marketplace\DTOs;

final readonly class StoreSkillDTO
{
    /**
     * @param  array<string, array{title: string}>  $translations
     */
    public function __construct(
        public ?int $categoryId,
        public array $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            translations: $validated['translations'],
        );
    }
}
