<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }
}
