<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\RoleRequest;
use App\Http\Resources\Dashboard\PermissionResource;
use App\Http\Resources\Dashboard\RoleCollection;
use App\Http\Resources\Dashboard\RoleResource;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
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
        $rows = Role::query()
            ->when($request->search, fn (Builder $q, $v) => $q->where('name', 'like', "%$v%"))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Roles/Index', [
            'prams' => fn () => $request->all() ?: [],
            'rows' => fn () => RoleCollection::make($rows),
        ]);
    }

    public function edit(Role $role)
    {
        $permissions = Permission::where('guard_name', 'admin')->get()->map(function (Permission $permission) {
            return PermissionResource::make($permission)
                ->only('id', 'name', 'group');
        })->groupBy('group');
        $role->load('permissions');

        return inertia('Dashboard/Roles/Edit', [
            'permissions' => $permissions,
            'role' => RoleResource::make($role),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'admin',
        ]);
        $role->syncPermissions($data['permissions']);

        return redirect()->route('dashboard.roles.index')->with('success', __('data saved successfully'));
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'admin')->get()->map(function (Permission $permission) {
            return PermissionResource::make($permission)
                ->only('id', 'name', 'group');
        })->groupBy('group');

        return inertia('Dashboard/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('dashboard.roles.index')->with('success', __('data deleted successfully '));
    }

    public function update(RoleRequest $request, Role $role)
    {
        $data = $request->validated();
        $role->update([
            'name' => $data['name'],
        ]);
        $role->syncPermissions($data['permissions']);

        return redirect()->route('dashboard.roles.index')->with('success', __('data updated successfully '));
    }
}
