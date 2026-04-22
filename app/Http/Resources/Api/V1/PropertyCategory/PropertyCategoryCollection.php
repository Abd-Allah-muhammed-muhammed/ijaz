<?php

namespace App\Http\Resources\Api\V1\PropertyCategory;

use App\Http\Resources\Api\BaseCollection;

class PropertyCategoryCollection extends BaseCollection
{
    public $collects = PropertyCategoryResource::class;
}
