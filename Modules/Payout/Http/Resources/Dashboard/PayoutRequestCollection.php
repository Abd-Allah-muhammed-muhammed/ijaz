<?php

namespace Modules\Payout\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PayoutRequestCollection extends ResourceCollection
{
    public $collects = PayoutRequestResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
