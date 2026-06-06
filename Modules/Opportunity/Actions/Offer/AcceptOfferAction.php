<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Actions\Chat\OpenOpportunityChatAction;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityOfferAcceptedNotification;
use Throwable;

class AcceptOfferAction
{
    public function __construct(
        private readonly OpenOpportunityChatAction $openOpportunityChatAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, OpportunityOffer $offer): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $offer) {
            $offer->update(['status' => OfferStatusEnum::Accepted]);

            $opportunity->offers()
                ->where('id', '!=', $offer->id)
                ->where('status', OfferStatusEnum::Pending)
                ->update(['status' => OfferStatusEnum::Rejected]);

            $opportunity->update([
                'status' => OpportunityStatusEnum::OfferAccepted,
                'accepted_offer_id' => $offer->id,
            ]);

            $opportunity->load(['author', 'acceptedOffer.author']);

            $this->openOpportunityChatAction->handle($opportunity);

            $offer->refresh()->load('author');
            $offer->author->notify(new OpportunityOfferAcceptedNotification($offer));

            $opportunity->load([
                'author',
                'region.translation',
                'city.translation',
                'media',
                'acceptedOffer.author',
            ]);

            return $opportunity;
        });
    }
}
