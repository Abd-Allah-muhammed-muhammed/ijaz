<?php

namespace Modules\Opportunity\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Opportunity\Contracts\Repositories\OpportunityCommentRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;

class OpportunityCommentRepository implements OpportunityCommentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OpportunityComment
    {
        return OpportunityComment::query()->create($data);
    }

    public function listByOpportunity(Opportunity $opportunity, int $perPage = 10): LengthAwarePaginator
    {
        return $opportunity->comments()
            ->with(['author'])
            ->latest()
            ->paginate($perPage);
    }
}
