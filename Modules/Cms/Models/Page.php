<?php

namespace Modules\Cms\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Translatable;

    protected $fillable = ['slug', 'composed_of_slugs'];

    public $translatedAttributes = ['title', 'content'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'composed_of_slugs' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isComposed(): bool
    {
        return is_array($this->composed_of_slugs) && $this->composed_of_slugs !== [];
    }
}
