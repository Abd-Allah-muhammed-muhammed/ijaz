<?php

namespace App\DTOs\Auth;

use App\Models\ProviderRegistrationUpload;

final readonly class ProviderRegistrationUploadDTO
{
    public function __construct(
        public int $id,
        public string $token,
        public string $field,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {}

    public static function fromModel(ProviderRegistrationUpload $upload): self
    {
        return new self(
            id: $upload->id,
            token: $upload->token,
            field: $upload->field,
            originalName: $upload->original_name,
            mimeType: $upload->mime_type,
            size: $upload->size,
        );
    }

    /**
     * @return array{id: int, token: string, field: string, original_name: string, mime_type: string, size: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'field' => $this->field,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
        ];
    }
}
