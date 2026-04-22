<?php

namespace App\Http\Resources\Api\V1\PropertyType;

use App\Http\Resources\Api\BaseCollection;

class PropertyTypeCollection extends BaseCollection
{
    public $collects = PropertyTypeResource::class;
}