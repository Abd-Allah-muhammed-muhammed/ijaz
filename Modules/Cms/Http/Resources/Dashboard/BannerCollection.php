<?php

namespace Modules\Cms\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Cms\Models\Banner;

/** @see Banner */
class BannerCollection extends ResourceCollection
{
    public $collects = BannerResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
