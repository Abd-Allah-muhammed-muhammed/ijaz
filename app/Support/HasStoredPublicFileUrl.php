<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

trait HasStoredPublicFileUrl
{
    protected function storedPublicFileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return $this->defaultImagePlaceholder();
        }

        return Storage::disk($this->storedFileDisk())->url($path);
    }

    protected function storedFileDisk(): string
    {
        return 'public';
    }

    protected function defaultImagePlaceholder(): ?string
    {
        return null;
    }
}
