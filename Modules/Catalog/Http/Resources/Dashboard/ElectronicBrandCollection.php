<?php

namespace Modules\Catalog\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ElectronicBrandCollection extends ResourceCollection
{
    public $collects = ElectronicBrandResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
