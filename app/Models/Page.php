<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Translatable;

    protected $fillable = ['slug'];

    public $translatedAttributes = ['title', 'content'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
