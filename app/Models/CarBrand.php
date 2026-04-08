<?php

namespace App\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CarBrand extends Model implements IReactSelect
{
    use Translatable;

    protected $fillable = [
        'is_active',
        'image',
    ];

    protected $translatedAttributes = ['name'];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            return $this->image ? Storage::disk('public')->url($this->image) : null;
        });
    }

    public function getLabel(): string
    {
        return $this->name ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }
}
