<?php

namespace App\Models;

use App\Services\Normalize\Normalize;
use Illuminate\Database\Eloquent\Model;

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
