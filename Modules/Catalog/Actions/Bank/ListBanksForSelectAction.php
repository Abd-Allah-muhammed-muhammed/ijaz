<?php

namespace Modules\Catalog\Actions\Bank;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Models\Bank;

class ListBanksForSelectAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Bank>
     */
    public function handle(?string $search = null): Collection
    {
        return $this->repository->listForSelect($search);
    }
}
