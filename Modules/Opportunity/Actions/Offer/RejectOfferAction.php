<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Throwable;

class RejectOfferAction
{
    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, OpportunityOffer $offer): void
    {
        DB::transaction(function () use ($offer) {
            $offer->update(['status' => OfferStatusEnum::Rejected]);

            // TODO: notify offer author
        });
    }
}
