<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class CarCategoryCollection extends BaseCollection
{
    public $collects = CarCategoryResource::class;
}
