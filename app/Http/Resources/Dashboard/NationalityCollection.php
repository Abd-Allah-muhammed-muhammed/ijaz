<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Nationality */
class NationalityCollection extends ResourceCollection
{
    public $collects = NationalityResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
