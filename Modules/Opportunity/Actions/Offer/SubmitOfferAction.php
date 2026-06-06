<?php

namespace Modules\Opportunity\Actions\Offer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\DTOs\OfferData;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
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
            $offer = $this->offers->create([
                ...$data->toPersistenceArray(),
                'opportunity_id' => $opportunity->id,
                'author_type' => $author::class,
                'author_id' => $author->getKey(),
                'status' => OfferStatusEnum::Pending,
            ]);

            // TODO: dispatch PaymentInitiatedEvent

            $offer->load(['author']);

            return $offer;
        });
    }
}
