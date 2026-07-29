<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;

class ListWithdrawRequestsForOwnerAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
    ) {}

    public function handle(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $this->repository->paginateForOwner($owner, $perPage);
    }
}
