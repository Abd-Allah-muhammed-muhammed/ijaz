<?php

namespace Modules\Opportunity\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Actions\Offer\AcceptOfferAction;
use Modules\Opportunity\Actions\Offer\DeleteOfferAction;
use Modules\Opportunity\Actions\Offer\RejectOfferAction;
use Modules\Opportunity\Actions\Offer\SubmitOfferAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\DTOs\OfferData;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Throwable;

class OfferService
{
    public function __construct(
        private readonly OpportunityOfferRepositoryInterface $repository,
        private readonly SubmitOfferAction $submitAction,
        private readonly AcceptOfferAction $acceptAction,
        private readonly RejectOfferAction $rejectAction,
        private readonly DeleteOfferAction $deleteAction,
    ) {}

    public function listByOpportunity(Opportunity $opportunity, Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->listByOpportunity($opportunity, $actor, $perPage);
    }

    /**
     * @throws Throwable
     */
    public function submit(Opportunity $opportunity, OfferData $data, Model $author): OpportunityOffer
    {
        return $this->submitAction->handle($opportunity, $data, $author);
    }

    /**
     * @throws Throwable
     */
    public function accept(Opportunity $opportunity, OpportunityOffer $offer): Opportunity
    {
        return $this->acceptAction->handle($opportunity, $offer);
    }

    /**
     * @throws Throwable
     */
    public function reject(Opportunity $opportunity, OpportunityOffer $offer): void
    {
        $this->rejectAction->handle($opportunity, $offer);
    }

    public function deleteForDashboard(OpportunityOffer $offer): void
    {
        $this->deleteAction->handle($offer);
    }
}
