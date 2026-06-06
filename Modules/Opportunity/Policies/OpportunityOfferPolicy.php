<?php

namespace Modules\Opportunity\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;

class OpportunityOfferPolicy
{
    public function create(Model $user, Opportunity $opportunity): bool
    {
        return $opportunity->status === OpportunityStatusEnum::New;
    }
}
