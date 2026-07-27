<?php

namespace Modules\Catalog\Models;

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

    protected $fillable = ['title', 'locale'];

    /*
     * TODO (deferred — not part of QueryFilters consolidation):
     * `propertiy_category_translations.normalized_title` exists and is indexed, and
     * PropertyCategory search filters against it via TranslationSearchFilter, but this
     * model never populates `normalized_title` on save (unlike Specialization /
     * CarCategory / DeviceCategory / ElectronicBrand translations). Until a saving
     * hook (or equivalent) writes Normalize::make($title, $locale), Arabic-normalized
     * PropertyCategory search will match nothing. See PROJECT_CONTEXT.md §7.
     */

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
