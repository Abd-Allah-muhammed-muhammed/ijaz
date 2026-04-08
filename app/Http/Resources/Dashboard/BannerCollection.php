<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Banner */
class BannerCollection extends ResourceCollection
{
    public $collects = BannerResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
