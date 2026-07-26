<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class DeleteAllNotificationsAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user): void
    {
        $this->repository->deleteAllNotifications($user);
    }
}
