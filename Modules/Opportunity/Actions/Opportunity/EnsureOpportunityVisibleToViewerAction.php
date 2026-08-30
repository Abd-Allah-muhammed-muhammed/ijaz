<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;

class EnsureOpportunityVisibleToViewerAction
{
    public function handle(Opportunity $opportunity, ?Model $viewer): void
    {
        if (! $opportunity->status->isIn([
            OpportunityStatusEnum::PendingAdmin,
            OpportunityStatusEnum::RejectedByAdmin,
        ])) {
            return;
        }

        if (
            $viewer !== null
            && $opportunity->author_type === $viewer::class
            && (string) $opportunity->author_id === (string) $viewer->getKey()
        ) {
            return;
        }

        throw (new ModelNotFoundException)->setModel(Opportunity::class, [$opportunity->getKey()]);
    }
}
