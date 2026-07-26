<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use App\DTOs\Account\DeleteNotificationResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class DeleteNotificationAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user, DatabaseNotification $notification): DeleteNotificationResult
    {
        $owned = $this->repository->findNotificationForUser($user, (string) $notification->getKey());

        if ($owned === null) {
            return DeleteNotificationResult::notFound();
        }

        $this->repository->deleteNotification($owned);

        return DeleteNotificationResult::deleted();
    }
}
