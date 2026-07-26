<?php

namespace Modules\Geo\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Geo\Database\Factories\RegionFactory;

class Region extends Model implements IReactSelect
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory, Translatable;

    public array $translatedAttributes = ['title', 'normalized_title'];

    protected $fillable = [''];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class, 'region_id');
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
        return RegionFactory::new();
    }
}
