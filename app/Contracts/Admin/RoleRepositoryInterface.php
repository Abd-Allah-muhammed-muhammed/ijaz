<?php

namespace App\Contracts\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @param  array{name: string, guard_name: string}  $data
     */
    public function create(array $data): Role;

    /**
     * @param  array{name: string}  $data
     */
    public function update(Role $role, array $data): Role;

    /**
     * @param  list<int|string>  $permissionIds
     */
    public function syncPermissions(Role $role, array $permissionIds): void;

    public function delete(Role $role): void;

    public function loadForEdit(Role $role): Role;

    /**
     * @return Collection<int, Role>
     */
    public function getAllForAdminGuard(): Collection;

    public function adminGuardRoleExists(string $roleName): bool;

    /**
     * @return Collection<int, Permission>
     */
    public function listAdminPermissions(): Collection;
}
