<?php

namespace Modules\Marketplace\Models;

use App\Models\Provider;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasPermissions;

class ProviderType extends Model
{
    use HasPermissions, Translatable;

    public string $guard_name = 'provider';

    public array $translatedAttributes = ['name', 'description'];

    protected string $default_image = 'media/avatars/blank.png';

    protected $fillable = ['files', 'image'];

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class, 'provider_type_id');
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
            return $this->image ? Storage::disk('public')->url($this->image) : asset($this->default_image);
        });
    }

    protected function casts(): array
    {
        return [
            'files' => 'array',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'provider_type_categories');
    }
}
