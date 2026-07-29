<?php

namespace Modules\Cms\DTOs;

final readonly class UpdateQuestionDTO
{
    /**
     * @param  array<string, array{title: string, answer: string}>  $translations
     */
    public function __construct(
        public array $translations,
    ) {}

    /**
     * @param  array{translations: array<string, array{title: string, answer: string}>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(translations: $validated['translations']);
    }
}
