<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    protected $fillable = [
        'link',
        'image',
    ];

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            return $this->image ? Storage::disk('public')->url($this->image) : asset($this->default_image);
        });
    }
}
