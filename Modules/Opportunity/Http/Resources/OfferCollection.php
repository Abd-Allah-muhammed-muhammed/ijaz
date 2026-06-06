<?php

namespace Modules\Opportunity\Http\Resources;

use App\Http\Resources\Api\BaseCollection;

class OfferCollection extends BaseCollection
{
    public $collects = OfferResource::class;
}
