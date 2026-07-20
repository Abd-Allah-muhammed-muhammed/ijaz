<?php

namespace Modules\Geo\Models;

use App\Contracts\Selects\IReactSelect;
use App\Models\User;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nationality extends Model implements IReactSelect
{
    use Translatable;

    public array $translatedAttributes = ['name', 'normalized_name'];

    protected $fillable = ['code', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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
