<?php

namespace Modules\Support\Http\Resources\Api;

use App\Http\Resources\Api\BaseCollection;

/**
 * @extends BaseCollection<TicketSupportResource>
 *
 * @see TicketSupportResource
 */
class TicketSupportCollection extends BaseCollection
{
    public $collects = TicketSupportResource::class;
}
