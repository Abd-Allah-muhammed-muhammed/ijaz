<?php

namespace Modules\Reviews\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Reviews\Models\Review;

/** @see Review */
class ReviewCollection extends ResourceCollection
{
    public $collects = ReviewResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
