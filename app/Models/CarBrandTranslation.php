<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarBrandTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
    }
}
