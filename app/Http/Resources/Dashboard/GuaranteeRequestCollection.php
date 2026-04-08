<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \App\Models\GuaranteeRequest */
class GuaranteeRequestCollection extends ResourceCollection
{
    public $collects = GuaranteeRequestResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
