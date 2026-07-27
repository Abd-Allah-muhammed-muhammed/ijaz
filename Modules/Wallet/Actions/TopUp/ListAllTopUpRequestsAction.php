<?php

namespace Modules\Wallet\Actions\TopUp;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;

class ListAllTopUpRequestsAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
    ) {}

    public function handle(int $perPage = 16): LengthAwarePaginator
    {
        return $this->repository->paginateAll($perPage);
    }
}
