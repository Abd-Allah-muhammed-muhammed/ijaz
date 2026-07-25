<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\DTOs\Admin\StoreAdminDTO;
use App\Models\Admin;

class StoreAdminAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    public function handle(StoreAdminDTO $dto): Admin
    {
        $admin = $this->repository->create([
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'password' => $dto->password,
            'address' => $dto->address,
            'job' => $dto->job,
            'image' => $dto->image->store('admins', 'public'),
        ]);

        $this->repository->attachRoles($admin, $dto->roles);

        return $admin;
    }
}
