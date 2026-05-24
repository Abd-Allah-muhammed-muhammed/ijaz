<?php

namespace Modules\Classifieds\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class PropertyAdvisementCollection extends BaseCollection
{
    public $collects = PropertyAdvisementResource::class;
}
