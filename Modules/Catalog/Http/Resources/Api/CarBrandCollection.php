<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class CarBrandCollection extends BaseCollection
{
    public $collects = CarBrandResource::class;
}
