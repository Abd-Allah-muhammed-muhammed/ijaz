<?php

namespace Modules\Opportunity\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
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

    public function listByOpportunity(Opportunity $opportunity, Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        $isAuthor = $opportunity->author_type === $actor::class
            && $opportunity->author_id === $actor->getKey();

        $query = $opportunity->offers()->with(['author']);

        if (! $isAuthor) {
            $query->where('author_type', $actor::class)
                ->where('author_id', $actor->getKey());
        }

        return $query->latest()->paginate($perPage);
    }

    public function findById(string $id): OpportunityOffer
    {
        return OpportunityOffer::query()->findOrFail($id);
    }
}