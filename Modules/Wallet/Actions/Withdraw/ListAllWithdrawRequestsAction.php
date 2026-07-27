<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;

class ListAllWithdrawRequestsAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
    ) {}

    public function handle(int $perPage = 16): LengthAwarePaginator
    {
        return $this->repository->paginateAll($perPage);
    }
}
