<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\PropertiyCategoryFactory;

/**
 * App\Models\PropertiyCategory
 *
 * @property int $id
 * @property int|null $parent_id
 * @property bool $is_active
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read PropertiyCategory|null $parent
 * @property-read Collection<int, PropertiyCategory> $children
 * @property-read int|null $children_count
 * @property-read Collection<int,PropertiyCategoryTranslation> $translations
 * @property-read PropertiyCategoryTranslation|null $translation
 * @property-read int|null $translations_count
 */
class PropertiyCategory extends Model implements IReactSelect
{
    /** @use HasFactory<PropertiyCategoryFactory> */
    use HasFactory, Translatable;

    protected $fillable = [
        'parent_id',
        'is_active',
    ];

    protected $translatedAttributes = ['title'];

    public function parent()
    {
        return $this->belongsTo(PropertiyCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PropertiyCategory::class, 'parent_id');
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
        return PropertiyCategoryFactory::new();
    }
}
