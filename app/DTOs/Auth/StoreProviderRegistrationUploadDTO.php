<?php

namespace App\DTOs\Auth;

use Illuminate\Http\UploadedFile;

final readonly class StoreProviderRegistrationUploadDTO
{
    public function __construct(
        public string $token,
        public string $field,
        public UploadedFile $file,
    ) {}

    /**
     * @param  array{token: string, field: string, file: UploadedFile}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            token: $validated['token'],
            field: $validated['field'],
            file: $validated['file'],
        );
    }
}
