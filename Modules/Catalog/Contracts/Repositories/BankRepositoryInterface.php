<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Bank;

interface BankRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): Bank;

    public function create(array $attributes): Bank;

    public function update(Bank $bank, array $attributes): Bank;

    public function delete(Bank $bank): void;

    public function restore(Bank $bank): void;

    public function loadForEdit(Bank $bank): Bank;

    /**
     * @return Collection<int, Bank>
     */
    public function getAllForDropdown(): Collection;

    /**
     * @return Collection<int, Bank>
     */
    public function listForSelect(?string $search = null): Collection;

    public function paginateForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator;
}
