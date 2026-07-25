<?php

namespace App\Contracts\Admin;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface AdminManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Admin;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Admin $admin, array $data): Admin;

    /**
     * @param  list<int|string>  $roleIds
     */
    public function syncRoles(Admin $admin, array $roleIds): void;

    /**
     * @param  list<int|string>  $roleIds
     */
    public function attachRoles(Admin $admin, array $roleIds): void;

    public function delete(Admin $admin): void;

    public function loadForEdit(Admin $admin): Admin;
}
