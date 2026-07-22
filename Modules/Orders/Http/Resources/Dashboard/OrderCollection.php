<?php

namespace Modules\Orders\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Orders\Models\Order;

/** @see Order */
class OrderCollection extends ResourceCollection
{
    public $collects = OrderResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
