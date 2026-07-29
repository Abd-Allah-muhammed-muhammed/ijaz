<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use App\Support\HasStoredFileUrl;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Database\Factories\SpecializationFactory;

class Specialization extends Model implements IReactSelect, TranslatableContract
{
    /** @use HasFactory<SpecializationFactory> */
    use HasFactory, HasStoredFileUrl, Translatable;

    protected $fillable = [
        'icon',
        'parent_id',
    ];

    public $translatedAttributes = ['title'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function deleteIcon(): void
    {
        if ($this->icon) {
            Storage::delete($this->icon);
        }
    }

    public function getLabel(): string
    {
        return $this->title ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => $this->storedFileUrl($this->icon));
    }

    protected function storedFileDisk(): string
    {
        return 'public';
    }

    protected function defaultImagePlaceholder(): ?string
    {
        return null;
    }

    protected static function newFactory(): SpecializationFactory
    {
        return SpecializationFactory::new();
    }
}
