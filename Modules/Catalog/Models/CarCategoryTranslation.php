<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Services\Normalize\Normalize;

class CarCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title'];

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('title')) {
                $model->normalized_title = Normalize::make($model->title, $model->locale);
            }
        });
    }
}
