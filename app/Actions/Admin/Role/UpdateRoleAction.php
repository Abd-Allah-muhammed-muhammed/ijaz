<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use App\DTOs\Admin\UpdateRoleDTO;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(Role $role, UpdateRoleDTO $dto): Role
    {
        $role = $this->repository->update($role, [
            'name' => $dto->name,
        ]);

        $this->repository->syncPermissions($role, $dto->permissions);

        return $role;
    }
}
