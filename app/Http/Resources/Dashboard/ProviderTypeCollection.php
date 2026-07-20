<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Geo\Models\City;

/** @see City */
class ProviderTypeCollection extends ResourceCollection
{
    public $collects = ProviderTypeResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
