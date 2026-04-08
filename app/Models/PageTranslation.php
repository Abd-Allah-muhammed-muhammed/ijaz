<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'content'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
