<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class SpecializationCollection extends BaseCollection
{
    public $collects = SpecializationResource::class;
}
