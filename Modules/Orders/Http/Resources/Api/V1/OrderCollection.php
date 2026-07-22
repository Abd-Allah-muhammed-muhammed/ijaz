<?php

namespace Modules\Orders\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class OrderCollection extends BaseCollection
{
    public $collects = OrderResource::class;
}
