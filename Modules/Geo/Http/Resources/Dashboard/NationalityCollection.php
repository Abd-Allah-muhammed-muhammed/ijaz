<?php

namespace Modules\Geo\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Geo\Models\Nationality;

/** @see Nationality */
class NationalityCollection extends ResourceCollection
{
    public $collects = NationalityResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
