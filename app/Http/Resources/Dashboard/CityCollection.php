<?php

namespace App\Http\Resources\Dashboard;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see City */
class CityCollection extends ResourceCollection
{
    public $collects = CityResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
