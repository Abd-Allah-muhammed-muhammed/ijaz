<?php

namespace Modules\Opportunity\Http\Resources;

use App\Http\Resources\Api\BaseCollection;

class OpportunityCollection extends BaseCollection
{
    public $collects = OpportunityResource::class;
}
