<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(Role $role): void
    {
        $this->repository->delete($role);
    }
}
