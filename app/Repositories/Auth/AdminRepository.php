<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\AdminRepositoryInterface;
use App\Models\Admin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class AdminRepository implements AdminRepositoryInterface
{
    public function findAuthenticated(): ?Admin
    {
        return Auth::guard('admin')->user();
    }

    public function getWithPermission(string $permission): Collection
    {
        $permissionExists = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'admin')
            ->exists();

        if (! $permissionExists) {
            return collect();
        }

        return Admin::permission($permission)->get();
    }
}
