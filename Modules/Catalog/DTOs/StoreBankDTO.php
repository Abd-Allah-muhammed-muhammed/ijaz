<?php

namespace Modules\Catalog\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class StoreBankDTO
{
    /**
     * @param  array<string, array{name: string}>  $translations
     */
    public function __construct(
        public array $translations,
        public ?UploadedFile $logo,
        public bool $isActive,
    ) {}

    /**
     * @param  array{translations: array<string, array{name: string}>, is_active?: bool}  $validated
     */
    public static function fromValidated(array $validated, ?UploadedFile $logo = null): self
    {
        return new self(
            translations: $validated['translations'],
            logo: $logo,
            isActive: (bool) ($validated['is_active'] ?? true),
        );
    }
}
