<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityOfferRejectedNotification;
use Throwable;

class RejectOfferAction
{
    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, OpportunityOffer $offer, Model $actor): void
    {
        DB::transaction(function () use ($opportunity, $offer, $actor) {
            if (! $this->isOpportunityAuthor($actor, $opportunity)) {
                throw new OpportunityException('opportunity.unauthorized', 403);
            }

            if ($this->isOfferAuthor($actor, $offer)) {
                throw new OpportunityException('opportunity.cannot_reject_own_offer', 403);
            }

            if ($offer->opportunity_id !== $opportunity->id) {
                throw new OpportunityException('opportunity.offer_not_belong_to_opportunity', 403);
            }

            $offer->update(['status' => OfferStatusEnum::Rejected]);

            $offer->refresh()->load('author');
            $offer->author->notify(new OpportunityOfferRejectedNotification($offer));
        });
    }

    private function isOpportunityAuthor(Model $actor, Opportunity $opportunity): bool
    {
        return $opportunity->author_type === $actor::class
            && (string) $opportunity->author_id === (string) $actor->getKey();
    }

    private function isOfferAuthor(Model $actor, OpportunityOffer $offer): bool
    {
        return $offer->author_type === $actor::class
            && (string) $offer->author_id === (string) $actor->getKey();
    }
}
