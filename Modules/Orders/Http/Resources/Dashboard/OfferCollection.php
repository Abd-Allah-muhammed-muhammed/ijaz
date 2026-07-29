<?php

namespace Modules\Orders\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Orders\Models\OrderOffer;

/** @see OrderOffer */
class OfferCollection extends ResourceCollection
{
    public $collects = OfferResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
