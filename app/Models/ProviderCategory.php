<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProviderCategory extends Pivot
{
    protected $fillable = [
        'category_id',
        'provider_id',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'provider_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
