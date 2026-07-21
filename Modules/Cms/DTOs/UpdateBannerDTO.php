<?php

namespace Modules\Cms\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UpdateBannerDTO
{
    public function __construct(
        public ?string $link,
        public ?UploadedFile $image,
    ) {}

    /**
     * @param  array{link?: ?string, image?: UploadedFile|null}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            link: $validated['link'] ?? null,
            image: $validated['image'] ?? null,
        );
    }
}
