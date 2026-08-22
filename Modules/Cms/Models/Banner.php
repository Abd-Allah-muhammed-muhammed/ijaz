<?php

namespace Modules\Cms\Models;

use App\Support\HasStoredFileUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use HasStoredFileUrl;

    protected $fillable = [
        'link',
        'image',
    ];

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk($this->storedFileDisk())->delete($this->image);
        }
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->storedFileUrl($this->image));
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
