<?php

namespace Modules\Opportunity\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

interface OpportunityCommentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpportunityComment;

    public function listByOpportunity(Opportunity $opportunity, int $perPage = 10): LengthAwarePaginator;
}
