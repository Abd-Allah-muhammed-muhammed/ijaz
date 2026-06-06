<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Facades\DB;
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
            $opportunity->delete();
        });
    }
}
