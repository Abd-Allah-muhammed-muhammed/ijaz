<?php

namespace Modules\Opportunity\Policies\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Models\Opportunity;

trait AuthorizesOpportunityAuthor
{
    protected function isAuthor(Model $user, Opportunity $opportunity): bool
    {
        return $opportunity->author_type === $user::class
            && $opportunity->author_id === $user->getKey();
    }
}
