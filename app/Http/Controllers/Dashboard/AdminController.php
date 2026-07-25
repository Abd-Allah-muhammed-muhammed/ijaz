<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Admin\StoreAdminDTO;
use App\DTOs\Admin\UpdateAdminDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AdminRequest;
use App\Http\Resources\Dashboard\AdminCollection;
use App\Http\Resources\Dashboard\AdminResource;
use App\Http\Resources\Dashboard\RoleResource;
use App\Models\Admin;
use App\Services\Admin\AdminManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AdminManagementService $adminService,
    ) {}

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
        return inertia('Dashboard/Admins/Index', [
            'prams' => $request->all() ?: [],
            'rows' => AdminCollection::make($this->adminService->index($request)),
        ]);
    }

    public function create()
    {
        return inertia('Dashboard/Admins/Create', [
            'roles' => RoleResource::collection($this->adminService->getRolesForDropdown()),
        ]);
    }

    public function store(AdminRequest $request)
    {
        $this->adminService->store(StoreAdminDTO::fromValidated(
            $request->validated(),
            $request->file('image'),
        ));

        return redirect()->route('dashboard.admins.index')->with('success', trans('data saved successfully'));
    }

    public function edit(Admin $admin)
    {
        $admin = $this->adminService->show($admin);

        return inertia('Dashboard/Admins/Edit', [
            'roles' => RoleResource::collection($this->adminService->getRolesForDropdown()),
            'admin' => AdminResource::make($admin),
        ]);
    }

    public function update(AdminRequest $request, Admin $admin)
    {
        $this->adminService->update($admin, UpdateAdminDTO::fromValidated(
            $request->validated(),
            $request->file('image'),
        ));

        return redirect()->route('dashboard.admins.index')->with('success', __('data saved successfully'));
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        $this->adminService->destroy($admin);

        return redirect()->route('dashboard.admins.index')->with('success', trans('data deleted successfully'));
    }
}
