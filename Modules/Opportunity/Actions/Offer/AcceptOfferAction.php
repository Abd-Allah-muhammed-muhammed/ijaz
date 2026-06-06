<?php

namespace Modules\Opportunity\Actions\Offer;

use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Throwable;

class AcceptOfferAction
{
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

            $opportunity->load(['author']);
            $offer->load(['author']);

            Conversation::firstOrCreate([
                'operation_type' => $opportunity::class,
                'operation_id' => $opportunity->id,
            ], [
                'user1_id' => $opportunity->author->getKey(),
                'user1_type' => $opportunity->author::class,
                'user2_id' => $offer->author->getKey(),
                'user2_type' => $offer->author::class,
            ]);

            // TODO: dispatch OpportunityOfferAcceptedEvent (for notifications)

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
