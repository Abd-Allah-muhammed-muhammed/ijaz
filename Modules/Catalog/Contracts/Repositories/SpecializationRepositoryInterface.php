<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Specialization;

interface SpecializationRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, Specialization>
     */
    public function getAll(Request $request): Collection;

    public function create(array $data): Specialization;

    public function update(Specialization $specialization, array $data): Specialization;

    public function delete(Specialization $specialization): void;

    public function findById(int $id): Specialization;

    public function find(int $id): ?Specialization;

    /**
     * @return Collection<int, Specialization>
     */
    public function getRootSpecializations(?int $excludeId = null): Collection;
}
