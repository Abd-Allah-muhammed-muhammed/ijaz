<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Geo\Models\Region;

/** @see Region */
class RegionCollection extends ResourceCollection
{
    public $collects = RegionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
