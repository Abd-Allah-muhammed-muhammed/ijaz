<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

trait HasStoredFileUrl
{
    protected function storedFileUrl(?string $path): ?string
    {
        if (blank($path)) {
            return $this->defaultImagePlaceholder();
        }

        return Storage::disk($this->storedFileDisk())->url($path);
    }

    protected function storedFileDisk(): string
    {
        return (string) config('filesystems.default');
    }

    protected function defaultImagePlaceholder(): ?string
    {
        return null;
    }
}
