<?php

namespace Modules\Cms\DTOs;

final readonly class UploadedPageContentImageDTO
{
    public function __construct(
        public string $url,
        public string $path,
    ) {}

    /**
     * @return array{url: string, path: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'path' => $this->path,
        ];
    }
}
