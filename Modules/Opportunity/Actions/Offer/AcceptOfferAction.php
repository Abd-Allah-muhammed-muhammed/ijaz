<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Actions\Chat\OpenOpportunityChatAction;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
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
            if ($opportunity->status->isNot(OpportunityStatusEnum::New)) {
                throw new OpportunityException('opportunity.cannot_accept_offer', 422);
            }

            if ($offer->opportunity_id !== $opportunity->id) {
                throw new OpportunityException('opportunity.offer_not_belong_to_opportunity', 403);
            }

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

            $this->openOpportunityChatAction->handle($opportunity, $opportunity->author);

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
