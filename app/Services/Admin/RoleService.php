<?php

namespace App\Services\Admin;

use App\Actions\Admin\Role\DeleteRoleAction;
use App\Actions\Admin\Role\GetRolesForDropdownAction;
use App\Actions\Admin\Role\ListAdminPermissionsAction;
use App\Actions\Admin\Role\ListRolesAction;
use App\Actions\Admin\Role\ShowRoleAction;
use App\Actions\Admin\Role\StoreRoleAction;
use App\Actions\Admin\Role\UpdateRoleAction;
use App\DTOs\Admin\StoreRoleDTO;
use App\DTOs\Admin\UpdateRoleDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly ListRolesAction $listAction,
        private readonly StoreRoleAction $storeAction,
        private readonly UpdateRoleAction $updateAction,
        private readonly DeleteRoleAction $deleteAction,
        private readonly ShowRoleAction $showAction,
        private readonly ListAdminPermissionsAction $listAdminPermissionsAction,
        private readonly GetRolesForDropdownAction $dropdownAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreRoleDTO $dto): Role
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Role $role, UpdateRoleDTO $dto): Role
    {
        return $this->updateAction->handle($role, $dto);
    }

    public function destroy(Role $role): void
    {
        $this->deleteAction->handle($role);
    }

    public function show(Role $role): Role
    {
        return $this->showAction->handle($role);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function listAdminPermissions(): Collection
    {
        return $this->listAdminPermissionsAction->handle();
    }

    /**
     * Shared Role dropdown for Admin create/edit forms.
     *
     * @return Collection<int, Role>
     */
    public function getAllForDropdown(): Collection
    {
        return $this->dropdownAction->handle();
    }
}
