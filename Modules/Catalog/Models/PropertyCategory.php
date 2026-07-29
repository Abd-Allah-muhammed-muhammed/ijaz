<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\PropertyCategoryFactory;

/**
 * App\Models\PropertyCategory
 *
 * @property int $id
 * @property int|null $parent_id
 * @property bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read PropertyCategory|null $parent
 * @property-read Collection<int, PropertyCategory> $children
 * @property-read int|null $children_count
 * @property-read Collection<int,PropertyCategoryTranslation> $translations
 * @property-read PropertyCategoryTranslation|null $translation
 * @property-read int|null $translations_count
 */
class PropertyCategory extends Model implements IReactSelect
{
    /** @use HasFactory<PropertyCategoryFactory> */
    use HasFactory, Translatable;

    protected $fillable = [
        'parent_id',
        'is_active',
    ];

    protected $translatedAttributes = ['title'];

    public function parent()
    {
        return $this->belongsTo(PropertyCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PropertyCategory::class, 'parent_id');
    }

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parent_id' => 'integer',
        ];
    }

    public function getLabel(): string
    {
        return $this->title ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }

    protected static function newFactory(): Factory
    {
        return PropertyCategoryFactory::new();
    }
}
