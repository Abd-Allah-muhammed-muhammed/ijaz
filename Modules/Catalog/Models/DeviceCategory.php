<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class DeviceCategory extends Model implements IReactSelect, TranslatableContract
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
            Storage::delete($this->icon);
        }
    }

    public function getLabel(): string
    {
        return $this->title ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }
}
