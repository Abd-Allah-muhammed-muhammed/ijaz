<?php

namespace Modules\Catalog\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

class DeviceCategoryCollection extends BaseCollection
{
    public $collects = DeviceCategoryResource::class;
}
