<?php

namespace App\Services\Firebase\Contract;

use App\Services\Firebase\DTO\FirebaseMessageTarget;
use Illuminate\Support\Collection;

interface InteractWithFirebase
{
    /**
     * @return FirebaseMessageTarget|iterable<int, FirebaseMessageTarget>|Collection<int, FirebaseMessageTarget>
     */
    public function routeNotificationForFirebase(): FirebaseMessageTarget|iterable;
}
