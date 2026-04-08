<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class JobCollection extends BaseCollection
{
    public $collects = JobResource::class;
}
