<?php

namespace Modules\Opportunity\Actions\Offer;

use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\Models\OpportunityOffer;

class DeleteOfferAction
{
    public function __construct(
        private readonly OpportunityOfferRepositoryInterface $repository,
    ) {}

    public function handle(OpportunityOffer $offer): void
    {
        $this->repository->delete($offer);
    }
}
