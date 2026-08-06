<?php

namespace App\Services\Firebase\Contract;

use App\Services\Firebase\DTO\Target;
use Illuminate\Support\Collection;

interface InteractWithFirebase
{
    /**
     * @return Target|iterable<int, Target>|Collection<int, Target>
     */
    public function routeNotificationForFirebase(): Target|iterable;
}
