<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AdminRequest;
use App\Http\Resources\Dashboard\AdminCollection;
use App\Http\Resources\Dashboard\AdminResource;
use App\Http\Resources\Dashboard\RoleResource;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;

class AdminController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:show admins', only: ['index', 'show']),
            new Middleware('permission:create admins', only: ['create', 'store']),
            new Middleware('permission:edit admins', only: ['edit', 'update']),
            new Middleware('permission:delete admins', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $rows = Admin::query()
            ->with('roles')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return inertia('Dashboard/Admins/Index', [
            'prams' => $request->all() ?: [],
            'rows' => AdminCollection::make($rows),
        ]);
    }

    public function edit(Admin $admin)
    {
        $roles = Role::where('guard_name', 'admin')->get();
        $admin->load('roles');

        return inertia('Dashboard/Admins/Edit', [
            'roles' => RoleResource::collection($roles),
            'admin' => AdminResource::make($admin),
        ]);
    }

    public function update(AdminRequest $request, Admin $admin)
    {

        $data = $request->validated();
        if ($request->hasFile('image')) {
            $admin->deleteImage();
            $data['image'] = $request->file('image')->store('admins', 'public');
        }
        if (! $request->filled('password')) {
            unset($data['password']);
        }
        $admin->update($data);
        $admin->roles()->sync($request->array('roles'));

        return redirect()->route('dashboard.admins.index')->with('success', __('data saved successfully'));
    }

    public function store(AdminRequest $request)
    {
        $data = $request->validated();
        $data['image'] = $request->file('image')->store('admins', 'public');
        $admin = Admin::create($data);
        $admin->roles()->attach($request->array('roles'));

        return redirect()->route('dashboard.admins.index')->with('success', trans('data saved successfully'));
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return inertia('Dashboard/Admins/Create', [
            'roles' => RoleResource::collection($roles),
        ]);
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $admin->deleteImage();
        $admin->delete();

        return redirect()->route('dashboard.admins.index')->with('success', trans('data deleted successfully'));
    }
}
