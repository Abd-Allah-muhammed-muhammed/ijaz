<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\PropertiyCategoryTranslation
 *
 * @property string $title
 * @property string $locale
 * @property int $propertiy_category_id
 */
class PropertiyCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title'];

    public function propertiyCategory(): BelongsTo
    {
        return $this->belongsTo(PropertiyCategory::class);
    }

    public function casts(): array
    {
        return [
            'title' => 'string',
        ];
    }
}
