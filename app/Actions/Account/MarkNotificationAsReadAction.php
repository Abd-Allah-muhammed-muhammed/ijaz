<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use App\DTOs\Account\MarkNotificationResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class MarkNotificationAsReadAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user, DatabaseNotification $notification): MarkNotificationResult
    {
        $owned = $this->repository->findNotificationForUser($user, (string) $notification->getKey());

        if ($owned === null) {
            return MarkNotificationResult::notFound();
        }

        if ($owned->read_at) {
            return MarkNotificationResult::alreadyRead();
        }

        $this->repository->markNotificationRead($owned);

        return MarkNotificationResult::marked();
    }
}
