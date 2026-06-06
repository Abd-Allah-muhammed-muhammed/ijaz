<?php

namespace Modules\Opportunity\Http\Controllers\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait AuthorizesOpportunityRequests
{
    protected function authorizeOrFail(string $ability, mixed $arguments, string $messageKey, int $status = 403): void
    {
        try {
            $this->authorize($ability, $arguments);
        } catch (AuthorizationException) {
            throw new HttpException($status, __($messageKey));
        }
    }
}
