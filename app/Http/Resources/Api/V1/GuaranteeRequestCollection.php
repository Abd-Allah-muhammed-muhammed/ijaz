<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class GuaranteeRequestCollection extends BaseCollection
{
    public $collects = GuaranteeRequestResource::class;
}
