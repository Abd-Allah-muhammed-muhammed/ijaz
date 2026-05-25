<?php

namespace Modules\Classifieds\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ElectronicAdvisementCollection extends ResourceCollection
{
    public $collects = ElectronicAdvisementResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
