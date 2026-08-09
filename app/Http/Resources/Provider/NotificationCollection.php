<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\Api\BaseCollection;

class NotificationCollection extends BaseCollection
{
    public $collects = NotificationResource::class;
}
