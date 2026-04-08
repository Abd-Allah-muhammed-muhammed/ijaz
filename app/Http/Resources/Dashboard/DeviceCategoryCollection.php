<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeviceCategoryCollection extends ResourceCollection
{
    public $collects = DeviceCategoryResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
