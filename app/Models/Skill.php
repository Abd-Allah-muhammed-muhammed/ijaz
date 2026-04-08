<?php

namespace App\Models;

use App\Contracts\Selects\IReactSelect;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model implements IReactSelect
{
    use Translatable;

    public array $translatedAttributes = ['title', 'normalized_title'];

    protected $fillable = [
        'category_id',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getLabel(): string
    {
        return $this->title;
    }

    public function getValue(): string
    {
        return $this->id;
    }
}
