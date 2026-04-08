<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Admin */
class AdminCollection extends ResourceCollection
{
    public $collects = AdminResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
