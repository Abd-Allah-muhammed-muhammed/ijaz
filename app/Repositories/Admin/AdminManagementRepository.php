<?php

namespace App\Repositories\Admin;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AdminManagementRepository implements AdminManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Admin::query()
            ->with('roles')
            ->when($request->search, fn ($q, $v) => $q->where('name', 'like', "%$v%"))
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Admin
    {
        return Admin::create($data);
    }

    public function update(Admin $admin, array $data): Admin
    {
        $admin->update($data);

        return $admin;
    }

    public function syncRoles(Admin $admin, array $roleIds): void
    {
        $admin->roles()->sync($roleIds);
    }

    public function attachRoles(Admin $admin, array $roleIds): void
    {
        $admin->roles()->attach($roleIds);
    }

    public function assignRoleByName(Admin $admin, string $roleName): void
    {
        $admin->assignRole($roleName);
    }

    public function markAsRoot(Admin $admin): void
    {
        $admin->forceFill(['root' => true])->save();
    }

    public function existsByEmail(string $email): bool
    {
        return Admin::query()->where('email', $email)->exists();
    }

    public function existsByPhone(string $phone): bool
    {
        return Admin::query()->where('phone', $phone)->exists();
    }

    public function delete(Admin $admin): void
    {
        $admin->deleteImage();
        $admin->delete();
    }

    public function loadForEdit(Admin $admin): Admin
    {
        return $admin->load('roles');
    }
}
