<?php

namespace Modules\Marketplace\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class CategoryCollection extends BaseCollection
{
    public $collects = CategoryResource::class;
}
