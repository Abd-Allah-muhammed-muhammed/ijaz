<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class NotificationCollection extends BaseCollection
{
    public $collects = NotificationResource::class;
}
