<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class CityCollection extends BaseCollection
{
    public $collects = CityResource::class;
}
