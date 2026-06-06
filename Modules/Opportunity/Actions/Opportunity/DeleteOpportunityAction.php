<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class DeleteOpportunityAction
{
    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity): void
    {
        DB::transaction(function () use ($opportunity) {
            if ($opportunity->status->isNot(OpportunityStatusEnum::New)) {
                throw new OpportunityException('opportunity.cannot_delete_non_new', 403);
            }

            $opportunity->delete();
        });
    }
}
