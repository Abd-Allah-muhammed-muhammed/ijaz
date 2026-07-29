<?php

namespace Modules\Geo\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Geo\Models\City;

/** @see City */
class CityCollection extends ResourceCollection
{
    public $collects = CityResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
