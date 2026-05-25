<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class PropertyCategoryCollection extends BaseCollection
{
    public $collects = PropertyCategoryResource::class;
}
