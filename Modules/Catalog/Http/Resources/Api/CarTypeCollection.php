<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class CarTypeCollection extends BaseCollection
{
    public $collects = CarTypeResource::class;
}
