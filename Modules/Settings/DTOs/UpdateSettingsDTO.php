<?php

namespace Modules\Settings\DTOs;

final readonly class UpdateSettingsDTO
{
    /**
     * @param  array<string, string>  $values
     * @param  array<string, bool>  $isPublic
     */
    public function __construct(
        public array $values,
        public array $isPublic,
    ) {}

    /**
     * @param  array{values: array<string, string>, is_public?: array<string, mixed>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        /** @var array<string, string> $values */
        $values = $validated['values'];

        /** @var array<string, mixed> $rawPublic */
        $rawPublic = $validated['is_public'] ?? [];

        $isPublic = [];
        foreach (array_keys($values) as $key) {
            $isPublic[$key] = filter_var($rawPublic[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        return new self(values: $values, isPublic: $isPublic);
    }

    /**
     * @return array<string, array{content: string, is_public: bool}>
     */
    public function toRepositoryUpdates(): array
    {
        $updates = [];

        foreach ($this->values as $key => $content) {
            $updates[$key] = [
                'content' => (string) $content,
                'is_public' => $this->isPublic[$key] ?? false,
            ];
        }

        return $updates;
    }
}
