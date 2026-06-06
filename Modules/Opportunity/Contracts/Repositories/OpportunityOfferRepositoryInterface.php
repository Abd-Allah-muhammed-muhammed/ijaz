<?php

namespace Modules\Opportunity\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;

interface OpportunityOfferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpportunityOffer;

    public function listByOpportunity(Opportunity $opportunity, int $perPage = 10): LengthAwarePaginator;

    public function findById(string $id): OpportunityOffer;
}
