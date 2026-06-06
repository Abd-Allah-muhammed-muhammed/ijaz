<?php

namespace Modules\Opportunity\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Models\Opportunity;

interface OpportunityRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data): Opportunity;

    public function findById(string $id): Opportunity;

    public function listPublic(int $perPage = 10): LengthAwarePaginator;

    public function listByActor(Model $actor, int $perPage = 10): LengthAwarePaginator;
}
