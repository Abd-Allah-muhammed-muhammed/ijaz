<?php

namespace App\DTOs\Account;

final readonly class UpdateAccountSettingsDTO
{
    public function __construct(
        public string $language,
    ) {}

    /**
     * @param  array{language: string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            language: (string) $validated['language'],
        );
    }

    /**
     * @return array{language: string}
     */
    public function toArray(): array
    {
        return [
            'language' => $this->language,
        ];
    }
}
