<?php

namespace Modules\Cms\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use Translatable;

    public $translatedAttributes = ['title', 'answer'];
}
