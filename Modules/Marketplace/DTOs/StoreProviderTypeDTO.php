<?php

namespace Modules\Marketplace\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class StoreProviderTypeDTO
{
    /**
     * @param  array<string, bool>  $files
     * @param  array<int, int>|null  $categories
     * @param  array<string, array{name: string, description?: string|null}>  $translations
     */
    public function __construct(
        public array $files,
        public UploadedFile $image,
        public ?array $categories,
        public array $translations,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated, UploadedFile $image): self
    {
        return new self(
            files: $validated['files'],
            image: $image,
            categories: $validated['categories'] ?? null,
            translations: $validated['translations'],
        );
    }
}
