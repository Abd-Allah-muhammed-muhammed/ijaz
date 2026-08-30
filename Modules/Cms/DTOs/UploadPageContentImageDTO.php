<?php

namespace Modules\Cms\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UploadPageContentImageDTO
{
    public function __construct(
        public UploadedFile $image,
    ) {}

    /**
     * @param  array{image: UploadedFile}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            image: $validated['image'],
        );
    }
}
