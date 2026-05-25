<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class PropertyTypeCollection extends BaseCollection
{
    public $collects = PropertyTypeResource::class;
}
