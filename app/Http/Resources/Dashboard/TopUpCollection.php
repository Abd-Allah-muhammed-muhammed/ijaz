<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Banner */
class TopUpCollection extends ResourceCollection
{
    public $collects = TopUpResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
