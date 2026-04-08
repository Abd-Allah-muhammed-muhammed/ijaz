<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class NationalityCollection extends BaseCollection
{
    public $collects = NationalityResource::class;
}
