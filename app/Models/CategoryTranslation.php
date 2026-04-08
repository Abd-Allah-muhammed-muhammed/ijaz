<?php

namespace App\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTranslation extends Model
{
    use HasNormalizedAttributes;

    protected $fillable = ['title', 'normalized_title', 'locale', 'description'];

    public $timestamps = false;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'title' => 'normalized_title',
        ];
    }
}
