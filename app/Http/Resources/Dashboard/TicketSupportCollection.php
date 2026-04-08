<?php

namespace App\Http\Resources\Dashboard;

use App\Models\TicketSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see TicketSupport */
class TicketSupportCollection extends ResourceCollection
{
    public $collects = TicketSupportResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
