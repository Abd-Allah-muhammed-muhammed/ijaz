<?php

namespace Modules\Marketplace\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderTypeCollection extends ResourceCollection
{
    public $collects = ProviderTypeResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
