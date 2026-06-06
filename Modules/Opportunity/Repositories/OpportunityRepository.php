<?php

namespace Modules\Opportunity\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;

class OpportunityRepository implements OpportunityRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity
    {
        return Opportunity::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $opportunity->update($data);

        return $opportunity;
    }

    public function findById(string $id): Opportunity
    {
        return Opportunity::query()->findOrFail($id);
    }

    public function listPublic(int $perPage = 10): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount(['offers', 'comments'])
            ->active()
            ->latest()
            ->paginate($perPage);
    }

    public function listByActor(Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        return Opportunity::query()
            ->byActor($actor)
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount(['offers', 'comments'])
            ->latest()
            ->paginate($perPage);
    }
}
