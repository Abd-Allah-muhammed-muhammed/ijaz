<?php

namespace App\Http\Resources\Api\V1;

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
