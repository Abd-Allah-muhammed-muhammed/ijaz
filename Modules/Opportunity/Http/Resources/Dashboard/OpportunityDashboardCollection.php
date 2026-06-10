<?php

namespace Modules\Opportunity\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class OpportunityDashboardCollection extends ResourceCollection
{
    public $collects = OpportunityDashboardResource::class;

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
