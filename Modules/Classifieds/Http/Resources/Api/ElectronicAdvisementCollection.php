<?php

namespace Modules\Classifieds\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class ElectronicAdvisementCollection extends BaseCollection
{
    public $collects = ElectronicAdvisementResource::class;
}
