<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Provider */
class ProviderCollection extends ResourceCollection
{
    public $collects = ProviderResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
