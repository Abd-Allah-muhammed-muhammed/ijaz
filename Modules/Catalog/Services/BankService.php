<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\Bank\DeleteBankAction;
use Modules\Catalog\Actions\Bank\ListBanksAction;
use Modules\Catalog\Actions\Bank\ListBanksForApiAction;
use Modules\Catalog\Actions\Bank\ListBanksForSelectAction;
use Modules\Catalog\Actions\Bank\ShowBankAction;
use Modules\Catalog\Actions\Bank\StoreBankAction;
use Modules\Catalog\Actions\Bank\UpdateBankAction;
use Modules\Catalog\Contracts\Services\BankServiceInterface;
use Modules\Catalog\DTOs\StoreBankDTO;
use Modules\Catalog\DTOs\UpdateBankDTO;
use Modules\Catalog\Models\Bank;

class BankService implements BankServiceInterface
{
    public function __construct(
        private readonly ListBanksAction $listAction,
        private readonly ListBanksForApiAction $listForApiAction,
        private readonly ListBanksForSelectAction $listForSelectAction,
        private readonly StoreBankAction $storeAction,
        private readonly UpdateBankAction $updateAction,
        private readonly DeleteBankAction $deleteAction,
        private readonly ShowBankAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreBankDTO $dto): Bank
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Bank $bank, UpdateBankDTO $dto): Bank
    {
        return $this->updateAction->handle($bank, $dto);
    }

    public function destroy(Bank $bank): void
    {
        $this->deleteAction->handle($bank);
    }

    public function show(Bank $bank): Bank
    {
        return $this->showAction->handle($bank);
    }

    public function listForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($search, $perPage);
    }

    /**
     * @return Collection<int, Bank>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
