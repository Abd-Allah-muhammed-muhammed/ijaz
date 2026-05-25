<?php

namespace Modules\Catalog\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CarCategory extends Model implements IReactSelect
{
    use Translatable;

    public array $translatedAttributes = ['title', 'normalized_title', 'description'];

    protected $fillable = [
        'icon',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id')->with('childrenRecursive.translation');
    }

    public function deleteIcon(): void
    {
        if ($this->icon) {
            Storage::disk('public')->delete($this->icon);
        }
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::get(fn () => $this->icon ? Storage::disk('public')->url($this->icon) : null);
    }

    public function getLabel(): string
    {
        return $this->title ?? '';
    }

    public function getValue(): string
    {
        return (string) $this->id;
    }
}
