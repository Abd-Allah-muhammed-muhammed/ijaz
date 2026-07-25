<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;

class ShowRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(Role $role): Role
    {
        return $this->repository->loadForEdit($role);
    }
}
