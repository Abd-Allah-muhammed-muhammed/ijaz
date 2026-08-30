<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreBankDTO;
use Modules\Catalog\DTOs\UpdateBankDTO;
use Modules\Catalog\Models\Bank;

interface BankServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreBankDTO $dto): Bank;

    public function update(Bank $bank, UpdateBankDTO $dto): Bank;

    public function destroy(Bank $bank): void;

    public function restore(Bank $bank): void;

    public function toggleActive(Bank $bank): Bank;

    public function show(Bank $bank): Bank;

    public function listForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * @return Collection<int, Bank>
     */
    public function listForSelect(?string $search = null): Collection;
}
