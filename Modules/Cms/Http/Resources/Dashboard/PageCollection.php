<?php

namespace Modules\Cms\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Cms\Models\Page;

/** @see Page */
class PageCollection extends ResourceCollection
{
    public $collects = PageResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
