<?php

namespace App\Services\Firebase\Contract;

use App\Services\Firebase\DTO\Target;

interface InteractWithFirebase
{
    public function routeNotificationForFirebase(): Target;
}
