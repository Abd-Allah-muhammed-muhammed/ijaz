<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Enums\Users\UserStatusEnum;
use App\Models\User;

class UpdateUserStatusAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(User $user, UpdateUserStatusDTO $dto): User
    {
        $user = $this->repository->saveStatus($user, $dto->status);

        if ($dto->status === UserStatusEnum::Blocked->value) {
            $this->repository->block($user, $dto->blockDays ?: 0, $dto->blockReason);
            $this->repository->revokeTokens($user);
        }

        if ($dto->status === UserStatusEnum::Deleted->value) {
            $this->repository->revokeTokens($user);
        }

        return $user;
    }
}
