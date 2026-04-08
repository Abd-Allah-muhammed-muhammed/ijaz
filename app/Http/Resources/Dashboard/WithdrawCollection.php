<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Banner */
class WithdrawCollection extends ResourceCollection
{
    public $collects = WithdrawResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
