<?php

namespace Modules\Opportunity\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Opportunity\Contracts\Repositories\OpportunityOfferRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;

class OpportunityOfferRepository implements OpportunityOfferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpportunityOffer
    {
        return OpportunityOffer::query()->create($data);
    }

    public function listByOpportunity(Opportunity $opportunity, int $perPage = 10): LengthAwarePaginator
    {
        return $opportunity->offers()
            ->with(['author'])
            ->latest()
            ->paginate($perPage);
    }

    public function findById(string $id): OpportunityOffer
    {
        return OpportunityOffer::query()->findOrFail($id);
    }
}
