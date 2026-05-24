<?php

namespace Modules\Classifieds\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class CarAdvisementCollection extends BaseCollection
{
    public $collects = CarAdvisementResource::class;
}
