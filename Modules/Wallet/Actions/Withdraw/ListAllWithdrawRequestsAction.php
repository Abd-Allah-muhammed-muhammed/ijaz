<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;

class ListAllWithdrawRequestsAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateAll($request);
    }
}
