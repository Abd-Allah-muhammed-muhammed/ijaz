<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SpecializationCollection extends ResourceCollection
{
    public $collects = SpecializationResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
