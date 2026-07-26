<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class ListNotificationsAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateNotifications($user, $perPage);
    }
}
