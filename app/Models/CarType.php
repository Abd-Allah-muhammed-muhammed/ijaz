<?php

namespace App\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CarType extends Model implements IReactSelect
{
    use Translatable;

    protected $fillable = [
        'is_active',
        'image',
        'car_brand_id',
    ];

    protected $translatedAttributes = ['name'];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
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
