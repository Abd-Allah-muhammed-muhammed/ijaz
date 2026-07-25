<?php

namespace App\Repositories\Admin;

use App\Contracts\Admin\RoleRepositoryInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Role::query()
            ->when($request->search, fn (Builder $q, $v) => $q->where('name', 'like', "%$v%"))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role;
    }

    public function syncPermissions(Role $role, array $permissionIds): void
    {
        $role->syncPermissions($permissionIds);
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function loadForEdit(Role $role): Role
    {
        return $role->load('permissions');
    }

    public function getAllForAdminGuard(): Collection
    {
        return Role::where('guard_name', 'admin')->get();
    }

    public function listAdminPermissions(): Collection
    {
        return Permission::where('guard_name', 'admin')->get();
    }
}
