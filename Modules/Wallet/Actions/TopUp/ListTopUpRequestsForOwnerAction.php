<?php

namespace Modules\Wallet\Actions\TopUp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;

class ListTopUpRequestsForOwnerAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
    ) {}

    public function handle(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $this->repository->paginateForOwner($owner, $perPage);
    }
}
