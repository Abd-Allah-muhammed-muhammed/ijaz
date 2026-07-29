<?php

namespace Modules\Support\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Support\Models\TicketSupport;

/** @see TicketSupport */
class TicketSupportCollection extends ResourceCollection
{
    public $collects = TicketSupportResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
