<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\Models\Admin;

class DeleteAdminAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    public function handle(Admin $admin): void
    {
        $this->repository->delete($admin);
    }
}
