<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Admin\StoreRoleDTO;
use App\DTOs\Admin\UpdateRoleDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\RoleRequest;
use App\Http\Resources\Dashboard\PermissionResource;
use App\Http\Resources\Dashboard\RoleCollection;
use App\Http\Resources\Dashboard\RoleResource;
use App\Services\Admin\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:show roles', only: ['index']),
            new Middleware('permission:create roles', only: ['create', 'store']),
            new Middleware('permission:update roles', only: ['edit', 'update']),
            new Middleware('permission:delete roles', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        return inertia('Dashboard/Roles/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => RoleCollection::make($this->roleService->index($request)),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/Roles/Create', [
            'permissions' => $this->groupedAdminPermissions(),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $this->roleService->store(StoreRoleDTO::fromValidated($request->validated()));

        return redirect()->route('dashboard.roles.index')->with('success', __('data saved successfully'));
    }

    public function edit(Role $role)
    {
        $role = $this->roleService->show($role);

        return inertia('Dashboard/Roles/Edit', [
            'permissions' => $this->groupedAdminPermissions(),
            'role' => RoleResource::make($role),
        ]);
    }

    public function update(RoleRequest $request, Role $role)
    {
        $this->roleService->update($role, UpdateRoleDTO::fromValidated($request->validated()));

        return redirect()->route('dashboard.roles.index')->with('success', __('data updated successfully '));
    }

    public function destroy(Role $role)
    {
        $this->roleService->destroy($role);

        return redirect()->route('dashboard.roles.index')->with('success', __('data deleted successfully '));
    }

    /**
     * @return Collection<string, Collection<int, array{id: mixed, name: mixed, group: mixed}>>
     */
    private function groupedAdminPermissions()
    {
        return $this->roleService->listAdminPermissions()
            ->map(function (Permission $permission) {
                return PermissionResource::make($permission)
                    ->only('id', 'name', 'group');
            })
            ->groupBy('group');
    }
}
