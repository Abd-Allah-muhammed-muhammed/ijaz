<?php

namespace App\Actions\User;

use App\Actions\DeviceToken\ClearAllDeviceTokensAction;
use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;

class UpdateUserStatusAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
        private readonly ClearAllDeviceTokensAction $clearAllDeviceTokensAction,
    ) {}

    public function handle(User $user, UpdateUserStatusDTO $dto): User
    {
        $previousStatus = $user->status instanceof UserStatusEnum
            ? $user->status->value
            : (string) $user->status;

        $user = $this->repository->saveStatus($user, $dto->status);

        // Notify before revoking tokens so Firebase still has a delivery target on block/delete.
        if (
            $previousStatus !== $dto->status
            && AccountStatusChangedNotification::shouldNotify($user, $dto->status)
        ) {
            $user->notify(new AccountStatusChangedNotification(
                account: $user,
                status: $dto->status,
            ));
        }

        if ($dto->status === UserStatusEnum::Blocked->value) {
            $this->repository->block($user, $dto->blockDays ?: 0, $dto->blockReason);
            $this->repository->revokeTokens($user);
            $this->clearAllDeviceTokensAction->handle($user);
        }

        if ($dto->status === UserStatusEnum::Deleted->value) {
            $this->repository->revokeTokens($user);
            $this->clearAllDeviceTokensAction->handle($user);
        }

        return $user;
    }
}
