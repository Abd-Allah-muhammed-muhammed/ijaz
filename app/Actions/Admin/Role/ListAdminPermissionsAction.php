<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class ListAdminPermissionsAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Permission>
     */
    public function handle(): Collection
    {
        return $this->repository->listAdminPermissions();
    }
}
