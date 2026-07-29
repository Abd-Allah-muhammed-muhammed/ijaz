<?php

namespace Modules\Marketplace\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;

class ProviderTypeCollection extends BaseCollection
{
    public $collects = ProviderTypeResource::class;

    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Paginator) {
            return parent::toArray($request);
        }

        $count = $this->collection->count();

        return [
            'items' => $this->collection,
            'total' => $count,
            'count' => $count,
            'per_page' => $count,
            'current_page' => 1,
            'last_page' => 1,
            'has_more_pages' => false,
        ];
    }
}
