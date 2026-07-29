<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\Models\Admin;

class ShowAdminAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    public function handle(Admin $admin): Admin
    {
        return $this->repository->loadForEdit($admin);
    }
}
