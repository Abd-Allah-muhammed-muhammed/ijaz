<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class DeviceCategory extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'icon',
        'parent_id',
    ];

    public $translatedAttributes = ['title'];

    public function parent()
    {
        return $this->belongsTo(DeviceCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(DeviceCategory::class, 'parent_id');
    }

    public function deleteIcon()
    {
        if ($this->icon) {
            \Illuminate\Support\Facades\Storage::delete($this->icon);
        }
    }
}
