<?php

namespace App\Actions\Admin\Role;

use App\Contracts\Admin\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class GetRolesForDropdownAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function handle(): Collection
    {
        return $this->repository->getAllForAdminGuard();
    }
}
