<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class GetUnreadNotificationsCountAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user): int
    {
        return $this->repository->unreadNotificationsCount($user);
    }
}
