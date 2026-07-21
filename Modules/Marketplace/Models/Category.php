<?php

namespace Modules\Marketplace\Models;

use App\Contracts\Selects\IReactSelect;
use App\Enums\CategoryFeesTypeEnum;
use Astrotomic\Translatable\Translatable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Storage;

class Category extends Model implements IReactSelect
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, Translatable;

    public array $translatedAttributes = ['title', 'normalized_title', 'description'];

    protected $fillable = [
        'icon',
        'parent_id',
        'fees',
        'fees_type',
    ];

    protected $casts = [
        'fees_type' => CategoryFeesTypeEnum::class,
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->with('childrenRecursive.translation');
    }

    public function deleteIcon(): void
    {
        if ($this->icon) {
            Storage::disk('public')->delete($this->icon);
        }
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class, 'category_id');
    }

    public function categorySkills(): HasMany
    {
        return $this->hasMany(CategorySkill::class, 'category_id');
    }

    public function providerSkills(): HasManyThrough
    {
        $r = $this->hasManyThrough(
            Skill::class,
            CategorySkill::class,
            'category_id',
            'id',
            'id',
            'skill_id'
        );
        if (auth('employee')->check()) {
            $r->withAttributes([
                'category_skill.provider_id' => auth('employee')->user()->provider_id,
            ]);
        }

        return $r;
    }

    public function getFees(float $amount): float
    {
        if ($this->fees_type->is(CategoryFeesTypeEnum::FIXED)) {
            return $this->fees;
        }
        if ($this->fees_type->is(CategoryFeesTypeEnum::PERCENTAGE)) {
            return ($this->fees / 100) * $amount;
        }

        return $this->parent?->getFees($amount) ?? 0.0;
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->icon) {
                return Storage::disk('public')->url($this->icon);
            }

            return null;
        });
    }

    public function providerTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProviderType::class, 'provider_type_categories');
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
        return CategoryFactory::new();
    }
}
