<?php

namespace Modules\Guarantor\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GuarantorDashboardCollection extends ResourceCollection
{
    public $collects = GuarantorDashboardResource::class;

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
