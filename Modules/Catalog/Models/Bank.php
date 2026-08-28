<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Database\Factories\BankFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bank extends Model implements HasMedia, IReactSelect
{
    /** @use HasFactory<BankFactory> */
    use HasFactory, InteractsWithMedia, Translatable;

    protected $fillable = [
        'is_active',
    ];

    public array $translatedAttributes = ['name', 'normalized_name'];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->useDisk('public')
            ->singleFile();
    }

    public function getLabel(): string
    {
        return $this->name ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }

    public function getLogoUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('logo');

        return $url !== '' ? $url : null;
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->getLogoUrl());
    }

    protected static function newFactory(): Factory
    {
        return BankFactory::new();
    }
}
