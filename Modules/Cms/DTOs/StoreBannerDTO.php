<?php

namespace Modules\Cms\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class StoreBannerDTO
{
    public function __construct(
        public ?string $link,
        public UploadedFile $image,
    ) {}

    /**
     * @param  array{link?: ?string, image: UploadedFile}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            link: $validated['link'] ?? null,
            image: $validated['image'],
        );
    }
}
