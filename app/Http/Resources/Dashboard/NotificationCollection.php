<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Api\BaseCollection;
use App\Http\Resources\Provider\NotificationResource;

/**
 * Reuses Provider NotificationResource shape (translated title/body) for the Admin inbox.
 */
class NotificationCollection extends BaseCollection
{
    public $collects = NotificationResource::class;
}
