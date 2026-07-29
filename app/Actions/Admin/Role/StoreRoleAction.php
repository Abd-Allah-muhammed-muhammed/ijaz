<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use App\DTOs\Admin\StoreRoleDTO;
use Spatie\Permission\Models\Role;

class StoreRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(StoreRoleDTO $dto): Role
    {
        $role = $this->repository->create([
            'name' => $dto->name,
            'guard_name' => 'admin',
        ]);

        $this->repository->syncPermissions($role, $dto->permissions);

        return $role;
    }
}
