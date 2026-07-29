<?php

namespace Modules\Catalog\Models;

use App\Support\Normalize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $title
 * @property string|null $normalized_title
 * @property string $locale
 * @property int $property_category_id
 */
class PropertyCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'locale'];

    protected static function booted(): void
    {
        static::saving(static function ($translation) {
            if ($translation->isDirty('title') && ! empty($translation->locale)) {
                $translation->normalized_title = Normalize::make($translation->title, $translation->locale)->toString();
            }
        });
    }

    public function propertyCategory(): BelongsTo
    {
        return $this->belongsTo(PropertyCategory::class);
    }

    public function casts(): array
    {
        return [
            'title' => 'string',
        ];
    }
}
