<?php

namespace Modules\Catalog\Actions\Bank;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;

class ListBanksForApiAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    public function handle(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($search, $perPage);
    }
}
