<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\DTOs\OfferData;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityOfferSubmittedNotification;
use Throwable;

class SubmitOfferAction
{
    public function __construct(
        private readonly OpportunityOfferRepositoryInterface $offers,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, OfferData $data, Model $author): OpportunityOffer
    {
        return DB::transaction(function () use ($opportunity, $data, $author) {
            $existingOffer = $opportunity->offers()
                ->where('author_type', $author::class)
                ->where('author_id', $author->getKey())
                ->whereIn('status', [
                    OfferStatusEnum::Pending,
                    OfferStatusEnum::Accepted,
                ])
                ->exists();

            if ($existingOffer) {
                throw new OpportunityException('opportunity.offer_already_submitted', 422);
            }

            $offer = $this->offers->create([
                ...$data->toPersistenceArray(),
                'opportunity_id' => $opportunity->id,
                'author_type' => $author::class,
                'author_id' => $author->getKey(),
                'status' => OfferStatusEnum::Pending,
            ]);

            // TODO: dispatch PaymentInitiatedEvent

            $offer->load(['author']);

            $opportunity->loadMissing('author');
            $opportunity->author->notify(new OpportunityOfferSubmittedNotification($offer));

            return $offer;
        });
    }
}
