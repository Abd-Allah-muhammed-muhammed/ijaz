<?php

namespace Modules\Cms\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Cms\Models\Message;

/** @see Message */
class MessageCollection extends ResourceCollection
{
    public $collects = MessageResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
