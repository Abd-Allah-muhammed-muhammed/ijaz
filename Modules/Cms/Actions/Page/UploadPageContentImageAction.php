<?php

namespace Modules\Cms\Actions\Page;

use Illuminate\Support\Facades\Storage;
use Modules\Cms\DTOs\UploadedPageContentImageDTO;
use Modules\Cms\DTOs\UploadPageContentImageDTO;
use RuntimeException;

/**
 * Stores a Pages editor image on the public disk (same convention as Banner uploads).
 */
final class UploadPageContentImageAction
{
    public const string DIRECTORY = 'pages/content';

    public const string DISK = 'public';

    public function handle(UploadPageContentImageDTO $dto): UploadedPageContentImageDTO
    {
        $path = $dto->image->store(self::DIRECTORY, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Failed to store page content image.');
        }

        $url = Storage::disk(self::DISK)->url($path);

        return new UploadedPageContentImageDTO(
            url: $url,
            path: $path,
        );
    }
}
