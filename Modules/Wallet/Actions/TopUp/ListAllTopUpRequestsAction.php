<?php

namespace Modules\Wallet\Actions\TopUp;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;

class ListAllTopUpRequestsAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateAll($request);
    }
}
