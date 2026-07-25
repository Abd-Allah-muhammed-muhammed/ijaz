<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\Models\User;

class EditUserAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(User $user): User
    {
        return $this->repository->loadForEdit($user);
    }
}
