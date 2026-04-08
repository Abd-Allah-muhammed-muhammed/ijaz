<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see \Spatie\Permission\Models\Role */
class RoleCollection extends ResourceCollection
{
    public $collects = RoleResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
