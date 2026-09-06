<?php

namespace App\DTOs\Provider;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

final readonly class ProviderProfileFileUploadResult
{
    public function __construct(
        public string $field,
        public string $fileName,
        public string $url,
        public string $mediaUuid,
    ) {}

    public static function fromMedia(Media $media): self
    {
        return new self(
            field: $media->collection_name,
            fileName: $media->file_name,
            url: route('media.file-path', $media),
            mediaUuid: (string) $media->uuid,
        );
    }

    /**
     * @return array{field: string, file_name: string, url: string, media_uuid: string}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'file_name' => $this->fileName,
            'url' => $this->url,
            'media_uuid' => $this->mediaUuid,
        ];
    }
}
