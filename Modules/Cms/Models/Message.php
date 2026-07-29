<?php

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['name', 'phone', 'title', 'content'];
}
