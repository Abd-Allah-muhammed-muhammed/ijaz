<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\PropertyTypeFactory;

class PropertyType extends Model implements IReactSelect
{
    /** @use HasFactory<PropertyTypeFactory> */
    use HasFactory, Translatable;

    protected $fillable = [
        'is_active',
    ];

    protected $translatedAttributes = ['name'];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getLabel(): string
    {
        return $this->name ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }

    protected static function newFactory(): Factory
    {
        return PropertyTypeFactory::new();
    }
}
